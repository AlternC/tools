This script is meant to change passwords of all the mails accounts on an alternc mail server

@todo more doc

## 0. Edit config and templates

### Config

Edit the 0-config.php according to your needs


### Templates

Templates are located in the "templates" directory. 

"template-cmd.php" is the BASH command used to send one email and save the output to a single log file.
"template-email.php" is the EMAIL you want to send containing to the user password

```
<?= $email ?> is the user's email
<?= $password ?> is the user's password
```


## 1. Generate temporary table and passwords

Use 1-generate-passwords.php to create a table filled with users and passwords


## 2. Generate emails and send commands from templates

Use 2-create-mails.php to build your emails and send commands

At this point you can review and test the operations by opening and tweaking content in the mails and cmd directories


## 3. Send emails

Once you're ready, use the 3-send-emails.php script which will execute all commands in the cmd directory.

Each will create its own log file under the mail-logs directory, each named {$email}.log


## 4. Change passwords

When time has come, actually push the passwords update using the 4-change_pass_mails.php


## 9. Cleanup

Finally, delete the unsecure temporary table using 9-cleanup.php
