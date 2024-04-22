<?php die(); ?>
Akeeba Backup 7.9.2
================================================================================
~ Improved error reporting on application error
# [HIGH] Backup widget appears in a multisite's blogs, where the plugin is not available.
# [MEDIUM] Sometimes not choosing a forced backup timezone could result in an error setting up a CRON job
# [LOW] Third party S3 implementations (e.g. DigitalOcean Spaces) send malformed HTTP headers which cause the post-processing to stop

Akeeba Backup 7.9.1.1
================================================================================
# [HIGH] Internal CronExpression error setting up and running WP-CRON jobs

Akeeba Backup 7.9.1
================================================================================
# [HIGH] Potential deadlock initialising the backup engine
# [HIGH] TypeError setting up a WP-CRON job
# [MEDIUM] HTTP PUT might fail on some servers
# [LOW] opcache_invalidate may not invalidate a file
# [LOW] Would not work on 32-bit versions of PHP

Akeeba Backup 7.9.0
================================================================================
+ Support for files and archives over 2GiB (JPA file format 1.3)
~ Disabled deprecated API methods
~ Improve the Schedule Automatic Backups page
# [LOW] Test FTP Connection button in the Configuration page does not work, configuration still saved correctly
# [HIGH] WP-CRON Scheduling was always using GMT to calculate the next run time instead of the configured backup timezone

Akeeba Backup 7.8.2
================================================================================
~ Disabled deprecated API methods
~ Improve the Schedule Automatic Backups page
~ Only Administrators can take backups (previously, Editors could)
~ Workaround for WordPress not properly clearing the OPcache for the Composer includes
# [LOW] Test FTP Connection button in the Configuration page does not work, configuration still saved correctly

Akeeba Backup 7.8.1.1
================================================================================
+ Namespaced third party dependencies to avoid error caused by third party plugins when setting up a WP-CRON schedule in Akeeba Backup

Akeeba Backup 7.8.1
================================================================================
~ Provision for the OVH CRON jobs in the CLI scripts
# [HIGH] Long running WP-CRON scheduled backups would always use profile #1 when resuming
# [LOW] The backup.php CLI script doesn't warn the user if settings decryption is unavailable.
# [LOW] The Manage Backups page didn't report the WP-CRON backup origin correctly

Akeeba Backup 7.8.0.1
================================================================================
! CORE version caused a Dashboard error by trying to register the widgets only shipped with the Pro version

Akeeba Backup 7.8.0
================================================================================
+ Pseudo-CRON with WP-CRON
+ Admin dashboard widgets
+ Option to treat failed uploads as a backup error
# [LOW] PHP Deprecated notice in the Configuration page (cosmetic)