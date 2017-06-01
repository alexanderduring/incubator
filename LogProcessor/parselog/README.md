
# Log Processor

## parselog.php

Syntax:
```
$ parselog.php <command> <date> <logfile>
```

where 
  * ```<command>``` can be __'list'__ or __'export'__
  * ```<date>``` can be a date in the format __'YYYY-MM-DD'__ or the word __'yesterday'__
  * ```<logfile>``` is the path to the logfile you want to parse.

### What does it do?

It ...
  * checks, if there is a rotated version of ```<logfile>``` at the same location with the name __'<logfile>.1'__
  * makes a local copy of the logfile, and of logfile.1 if it exists
  * reads the logfiles line by line and splits them into complete log entries
    This is done in order to handle multiline log entries containing linebreaks
  * filters all log entries that do not start with the specified ```<date>```
  * writes the log entries to 
    * __stdout__, if ```<command>``` is __'list'__
    * ```logs/YYYY-MM-DD--<logfile>```, if ```<command>``` is __'export'__
  * removes the local copies of the log files made in the beginning

### How to use?

If a cronjob is defined like this
```
0 1 * * * /path/to/parselog.php export yesterday /path/to/logfile
```

You will soon have a folder with files looking like this:
```
$ ls logs/
2017-05-29--example.log  2017-05-30--example.log  2017-05-31--example.log
```

This allows you to collect log messages on a daily basis from multiple servers into a centralized system like a database.
