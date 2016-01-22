# Migrate domains

These scripts are intended to allow migration from an alternc instance to another

It's a two step process

## On source (i.e. the server actually hosting the domains)

* Use `source_create_export_file.php`, this will create a json export file for you

Usage:

```
source_create_export_file.php [options]

    Options:
    -o /tmp/out.json, --output-file=/tmp/out.json    Export file name and
                                                     path
    -e /tmp/domains.txt, --exclude=/tmp/domains.txt  Path of a file
                                                     containing domains to
                                                     exclude
    -h, --help                                       show this help message
                                                     and exit
```

## On target (i.e. the server hosting the domains starting from now)

    Use target_create_domains.php

    This will use the json export file
    Usage:
      target_create_domains.php [options]

    Options:
      -i /tmp/out.json, --input-file=/tmp/out.json           Input file name
                                                             and path
      -r /tmp/missing.txt, --restrict-file=/tmp/missing.txt  Domain list, one
                                                             per line. Others
                                                             won't be imported.
      -u 2001, --force-uid=2001                              Force the domains
                                                             owner
      -h, --help                                             show this help
                                                             message and exit


