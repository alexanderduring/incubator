#!/usr/bin/env php
<?php


/**
 * @author Alexander During
 */
class Application {

    // Arguments
    private $command;
    private $date;
    private $sourceFile;

    private $logDir;



    public function __construct()
    {
        $this->logDir = __DIR__ . '/logs';
    }



    public function execute($arguments)
    {
        try {
            
            $this->processArguments($arguments);
            $localFiles = $this->makeLocalCopyOfLogFiles();

            $targetStream = $this->openTargetStream();
            $this->processFiles($localFiles, $targetStream);
            fclose($targetStream);

            $this->removeLocalCopies($localFiles);

        } catch (Exception $exception) {
            fwrite(STDERR, $exception->getMessage() . PHP_EOL);
        }
    }



    private function processArguments($arguments)
    {
        if (count($arguments) != 4) {
            throw new Exception("Usage: $arguments[0] (list|export) <YYYY-MM-DD>|yesterday <path/to/logfile>");
        }
        
        if (!in_array($arguments[1], ['list', 'export'])) {
            throw new Exception("Unknown command '$command'.");
        }

        if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}|^yesterday/', $arguments[2]) == 0) {
            throw new Exception("Unsupported date format '$arguments[2]'.");
        }
        
        if (!file_exists($arguments[3])) {
            throw new Exception("The file '$arguments[3]' does not exist.");
        }

        $this->command = $arguments[1];
        $this->date = $arguments[2];
        $this->sourceFile = realpath($arguments[3]);

        if ($this->date == 'yesterday') {
            $this->date = date('Y-m-d', strtotime('-1 day 13:00'));
        }
    }



    private function makeLocalCopyOfLogFiles()
    {
        # Check if rotated log file exists
        $sourceFileRotated = $this->sourceFile . '.1';
        $localFiles = [];
        if (file_exists($sourceFileRotated)) {
            $localFiles[] = $this->makeLocalCopyOfLogFile($sourceFileRotated);
        }

        # Copy file to working dir
        $localFiles[] = $this->makeLocalCopyOfLogFile($this->sourceFile);

        return $localFiles;
    }



    private function makeLocalCopyOfLogFile($sourceFile)
    {
        $localFile = __DIR__ . '/' . basename($sourceFile) . '.copy';
        copy($sourceFile, $localFile);

        return $localFile;
    }



    private function openTargetStream()
    {
        switch ($this->command) {
            case 'list':
                $target = 'php://stdout';
                break;
            case 'export':
                if (!file_exists($this->logDir)) {
                    mkdir($this->logDir);
                }
                $target = $this->logDir . '/' . $this->date . '--' . basename($this->sourceFile);
                break;
        }
        $targetStream = fopen($target, 'w');
    
        return $targetStream;
    }
    


    private function processFiles(array $files, $targetStream)
    {
        foreach ($files as $file) {
            $this->processFile($file, $targetStream);
        }
    }



    private function processFile($file, $targetStream)
    {
        # Open file for reading
        $fileStream = fopen($file, 'r');

        $lastLogEntry = '';
        $logEntry = '';

    
        while (($line = fgets($fileStream)) !== false) {

            # If line starts with YYYY-MM-DDTHH:MM:SS+HH:MM we have a new log entry
            if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}\+[0-9]{2}:[0-9]{2}/', $line) == 1) {

                $this->processLogEntry($logEntry, $targetStream);
                $logEntry = $line;

            } else {
                $logEntry .= $line;
            }
        }

        $this->processLogEntry($logEntry, $targetStream);

    
        if (!feof($fileStream)) {
            fwrite(STDERR, "Error: unexpected fgets() fail".PHP_EOL);
        }

        # Close streams
        fclose($fileStream);
    }



    private function processLogEntry($logEntry, $targetStream)
    {
        if (substr($logEntry, 0, 10) == $this->date) {
            $matchedLogEntry = str_replace(PHP_EOL, '__NEWLINE__', rtrim($logEntry)) . PHP_EOL;
            fwrite($targetStream, $matchedLogEntry);
        }
    }



    private function removeLocalCopies($files)
    {
        foreach ($files as $file) {
            unlink($file);
        }
    }
}


$application = new Application();
$application->execute($argv);
