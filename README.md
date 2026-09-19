# cms.soundfresh.id
This is the source code for SoundFresh New Panel or cms.soundfresh.id. This web application is made using Laravel 11 with PHP 8.2 and Composer 1 as package manager. The root directory of this application is on the public folder.

## Deployment
To deploy this web application, you are required to do several things:

 - Fill the environtment variables
 - Run commands
 - Add required files

### Environment Variables
The environtment variables are located in the .env file. If you don't find it, pleace copy the .env.example as .env. The .env file should contain these credentials:
- Main database
- Old panel database (optional for first time data migration)
- Email SMTP
- Google Drive credentials,
- WhatsApp API

You can consult to the previous installation if you are having difficulties filling out the .env file.

### Commands
There are two types of commands, one is to install required dependencies and the other is to prepare the database. You might want to skip preparing the database if you already have the SQL dump from the previous installation. These commands required to be run on PHP 8.2.

#### Installing dependencies
1. composer install

#### Preparing the database (optional)
 1. php artisan migrate:fresh
 2. php artisan db:seed --class=SystemSeeder
 3. php ./migrate_user.php
 4. php ./migrate_track.php

### Add required files
There are two files that need to be added into the /storage/app directory:
1. sign.jpeg for SIGNATURE1
2. JSON file for Google Drive Service Account credentials