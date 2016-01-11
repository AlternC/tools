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

How to use
----------

*First, you work on the SOURCE (i.e. the AlternC server where the emails are currently served).*

 1. **Use source_fix_db.php ** *(optional)*
It might only be useful on old AlternC databases. 
It eventually fixes misformed mailman addresses in database, for a clean state.    

 1. **Use source_create_export_file.php  **
This important step creates a mailbox export file which contains informations such as address, domain, etc.
Check the filters provided on the command line to include / exclude domains / addresses.

*Next, you work on TARGET the AlternC server where the emails should next be served*

 1. **Transfer the Export JSON file**
 This file is by default in the SOURCE /tmp folder (see below for default location).
 Copy it over SSH to your TARGET at the same default location. 
 
 1. **target_create_mailboxes.php ** 
On *TARGET* server. Uses the mailbox export file to populate the database with the new emails. Creates a source->target per mailbox file.  

 1. **target_sync_mailboxes.php**
On *TARGET* server. Uses the source->target per mailbox file to run rsync between the two hosts.


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
