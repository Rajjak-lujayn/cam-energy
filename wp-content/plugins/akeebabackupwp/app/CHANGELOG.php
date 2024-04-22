<?php die();?>
Akeeba Solo 7.9.2
================================================================================
~ Improved error reporting on application error
# [LOW] Third party S3 implementations (e.g. DigitalOcean Spaces) send malformed HTTP headers which cause the post-processing to stop

Akeeba Solo 7.9.1
================================================================================
~ Using our framework, AWF, as a Composer dependency
# [HIGH] Potential deadlock initialising the backup engine
# [MEDIUM] HTTP PUT might fail on some servers
# [LOW] opcache_invalidate may not invalidate a file
# [LOW] Would not work on 32-bit versions of PHP

Akeeba Solo 7.9.0
================================================================================
+ Support for files and archives over 2GiB (JPA file format 1.3)
~ Disabled deprecated API methods
~ Improve the Schedule Automatic Backups page
# [LOW] Test FTP Connection button in the Configuration page does not work, configuration still saved correctly

Akeeba Solo 7.8.1
================================================================================
+ Restoration: handle Joomla 4.2+ MFA options
~ Provision for the OVH CRON jobs in the CLI scripts
# [LOW] The backup.php CLI script doesn't warn the user if settings decryption is unavailable.

Akeeba Solo 7.8.0
================================================================================
+ Option to treat failed uploads as a backup error