# limesurvey-response-log

A LimeSurvey plugin to save a log from each survey page submit

# Requirements
Requires minimum LimeSurvey version 3.0.1

# Usage
## 1 Install 

### Via console

Change to LS plugins folder:
```
$ cd /your/limesurvey/path/plugins
```
Use git to clone into folder `ResponseLogger`:
```
$ git clone https://github.com/TonisOrmisson/lime-response-logger.git ResponseLogger
```


## 2 Activate plugin

During activation an additional db table 'lime_response_log' will be created.
All the logs are saved in this table.

