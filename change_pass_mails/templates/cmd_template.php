#! /bin/bash

/usr/bin/php /var/tmp/change_pass_mails/maj_mails/mail_logs/3-send-emails.php <?= $email ?> <?= $email ?> 2>&1 > /var/tmp/change_pass_mails/maj_mails/mail_logs/<?= $email ?>.log
