## Synopsis

This set of PHP script is for sysadmins willing to migrate emails from one AlternC 3+ instance to another. 

## Code Example

**Use script in bash:**

    $ php source_create_export_file.php -h
    ... or ...
    $ ./source_create_export_file.php -h

## Motivation

Exporting / Importing AlternC account is a common request amongst users. We chose to fix problems one after the other, and this is our solution for emails.

## Installation

git clone https://github.com/AlternC/alternc-tools.git


## API Reference


**Useful bits**

 - The scripts follow a certain order.  
 - The emails will be copied with their original passwords
 - Alias and catchalls are copied too
 - Mailman addresses are **not** copied
 - Some steps produce JSON files that will be used by the next script. 
 - Names of JSON files are default across scripts, set them by yourself at your own risk
 - You will need the rsync command for the last step
 - The sync / last step can be repeated over time

## How to use


### on the SOURCE 
(i.e. the AlternC server where the emails are currently served).

* **Use `source_fix_db.php`** *(optional)*
It might only be useful on old AlternC databases. 
It eventually fixes misformed mailman addresses in database, for a clean state.    

* **Use source_create_export_file.php  **
This important step creates a mailbox export file which contains informations such as address, domain, etc.
Check the filters provided on the command line to include / exclude domains / addresses.

```
Usage:
  ./source_create_export_file.php [options]

Options:
  -o /tmp/out.json, --output-file=/tmp/out.json  Export file name and path
  -d domain.com, --single-domain=domain.com      A single domain to export
  -a foobar, --single-account=foobar             A single account name (i.e. AlternC login) to export
  
  --exclude-mails=/tmp/mailboxes.txt             Path of a file containing mailboxes to exclude, separated by breaklines
  --include-mails=/tmp/mailboxes.txt             Path of a file containing mailboxes to include, separated by breaklines
  --exclude-domains=/tmp/domain.txt              Path of a file containing domains to include, separated by breaklines
  --include-domains=/tmp/domain.txt              Path of a file containing domains to exclude, separated by breaklines
  
  -h, --help                                     show this help message and exit
```

### on TARGET 
(the AlternC server where the emails should next be served)

* **Transfer the Export JSON file**
 This file is by default in the SOURCE /tmp folder (see below for default location).
 Copy it over SSH to your TARGET at the same default location. 

*  `target_create_mailboxes.php`

On *TARGET* server. Uses the mailbox export file to populate the database with the new emails. Creates a source->target per mailbox file.  

```
  ./target_create_mailboxes.php [options]

Options:
  --ignore-login=true                            Ignore the email's source AlternC login and use the new domain owner
  -i /tmp/out.json, --input-file=/tmp/out.json   Input file name and path
  -o /tmp/out.json, --output-file=/tmp/out.json  Export file name and path
  -h, --help                                     show this help message and exit
```

* `target_sync_mailboxes.php`

On *TARGET* server. Uses the source->target per mailbox file to run rsync between the two hosts.

```
  ./target_sync_mailboxes.php [options] source.server.fqdn

Options:
  -i /tmp/rsyncData.json, --input-file=/tmp/rsyncData.json  Input file name and path
  -l /tmp/rsyncLog.json, --rsync-log=/tmp/rsyncLog.json     Rsync log files
  -h, --help                                                show this help message and exit
```


Export files and logs
----------
**Export JSON file**

 - Default location: /tmp/alternc.mailboxes_export_out.json
 - Contains addresses, types, hashed passwords
 - Produced on SOURCE at step 2. 
 - Copy to same location on TARGET for step 3.

**Rsync JSON file**
 
 - Default location /tmp/alternc.mailboxes_export_rsync.json
 - Produced on TARGET at step 3. 
 - Use on TARGET at step 4

**Logs**

 - On TARGET: tmp/alternc.mailboxes_export_rsync.log
 - Contains the details of the import and syncing operations

## Tests

No tests provided yet.

## Contributors

Open to patches, push requests, etc..

## License

GPL v2 licencse
