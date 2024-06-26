# Notification / Messaging Services

## Project Scope
- Use different communication providers to provide an abstraction service for sending notifications to customers. 
- Ideally to have different service providers in case one of the services goes down
- Able to disable and switch to different providers and channels
- Throttling to limit the amount of notifications sent to users within an interval
- Tracking of notifications sent
## How to run 
1. Clone this repository 
`git clone https://github.com/GanTong/notification-new.git`
2. navigate into project directory 
`cd notification-app-new`
3. You must make sure that Docker is already installed on your system
4. Create `.env` file
5. `DB_CONNECTION=mysql`
    `DB_HOST=mysql`
    `DB_PORT=3306`
Since the Docker container uses MySQL database and phpmyadmin (port 8004), you can also configure other database services 
6. In the terminal run `docker-compose up -d --build` to start up docker container
7. Access http://localhost:8000 to see a Laravel welcome page
8. Generate key for Laravel application `docker-composer exec app php artisan key:generate`
9. Run mirgration `docker-compose exec app php artisan migrate`
10. Access DB PhpMyAdmin: http://localhost:8004
11. Access mailhog panel http://localhost:8025 for email testing

# Project Breakdown

## Twilio
- This project is built with docker compose so that it can run out of the box without having to install dependencies seperately. Twilio is integrated in this project with both `sms` and `whatsapp` channel options. To use it please install `twilio-php SDK` via composer by running `docker-compose exec app composer require twilio/sdk`
- We must also register with Twilio a free trial account, and to obtain a verified caller number for the API call. 
- To fill in these credentials under the `.env` file for `TWILIO_ACCOUNT_SID=`
  `TWILIO_AUTH_TOKEN=`
  `TWILIO_FROM_NUMBER=`  
- Twilio runs by default, there is also an option to bypass it with cookie `bypass_twilio_php`

## Throttling
You can find the custom-built rate limiter class under `app\libraries\RatelimiterLaravel`. It offers the option to throttle based on max hit, interval (e.g. set valid for one hour) and error message parameters. This class is not limitted to be used as a route-binding middleware, it can also be used anywhere in your code. A better version would be to build it totally from scartch using Redis, although in my opinion it would be an overkill for this task since the RatelimiterLaravel class can satisfy almost all the needs. The Redis version can do a bit more with the ability to store (with a expiry date option) values for better identifying user's identity such as user agent, IP addrress etc. as Redis key.

## Endpoints 
1. To send OTP http://localhost:8000/getConfirmationCode?channel={email/sms/whatsapp}&provider={Twilio/Local}&destination={*numberOremail}
2. To verify OTP http://localhost:8000/confirmCode?channel={email/sms/whatsapp}&provider={Twilio/Local}&destination={*numberOremail}&code={*code}
3. To check if code is already verified http://localhost:8000/isCodeVerified?channel={email/sms/whatsapp}&provider={Twilio/Local}&destination={*numberOremail}&code={*code} 

## Things that got left out due to time constraints
- Writting test cases with phpunit
- Unable to integrate more providers. I have only managed to set up Twilio that offers free sms and whatsapp verify services. Although there are other providers that are free to sign up, for example Telesign, but it seems the trial account credentials did not work for me. I ended up building a second failover provider service named `Local` that offers custom built email verification service. 
- Error Logger 
- Utilising Redis for storing and configuring configuration data
- Delay and resend notifications if all providers fail

## List of files
notification-app-new/app/Http/Controllers/app/Http/Controllers/VerificationController 

notification-app-new/app/Libraries /RatelimiterLaravel.php

notification-app-new/app/Mail/VerifyCodeEmail.php

notification-app-new/app/Models/Providers/Local.php

notification-app-new/app/Models/Providers/Twilio.php

notification-app-new/app/Models/Verification.php

notification-app-new/app/Repositories/VerificationRepository.php

notification-app-new/app/Services/ProviderService.php

notification-app-new/app/Services/VerificationService.php

notification-app-new/routes/web.php

notification-app-new/resources/views/mail/verify-code.blade.php

notification-app-new/database/migrations/2024_06_13_153907_create_verifications_table.php

notification-app-new/docker-compose.yml

