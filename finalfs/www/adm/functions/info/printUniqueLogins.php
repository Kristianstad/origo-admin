<?php

    function printUniqueLogins($lastlogins)
    {
        date_default_timezone_set('Europe/Stockholm');
        setlocale(LC_TIME, 'sv_SE.UTF-8');
        echo '<br><b>Antal unika AD-användare som har loggat in i någon karta</b><br>';
		
        // Remove empty values from $lastlogins
        $lastlogins=array_filter($lastlogins);
		
        // Convert lastlogin dates to Unix timestamps (start of day)
        $lastloginDayTimestamps = array_map(function($date) {
            return (new DateTime(substr($date, 0, 10), new DateTimeZone('Europe/Stockholm')))
                ->setTime(0, 0, 0)
                ->getTimestamp();
        }, $lastlogins);

        // Get timestamp for today
        $today = new DateTime('today', new DateTimeZone('Europe/Stockholm'));
        $todayTimestamp = $today->setTime(0, 0, 0)->getTimestamp();

        // Filter logins from today
        $todayLogins = array_filter($lastloginDayTimestamps, function($value) use ($todayTimestamp) {
            return is_int($value) && $value == $todayTimestamp;
        });

        echo 'Idag, '.$today->format('Y-m-d').': '.count($todayLogins).'<br>';

        // Get timestamp for first day of current week
        $firstDayOfWeek = new DateTime('Monday this week', new DateTimeZone('Europe/Stockholm'));
        $firstDayOfWeekTimestamp = $firstDayOfWeek->setTime(0, 0, 0)->getTimestamp();

        // Filter logins from current week
        $thisWeekLogins = array_filter($lastloginDayTimestamps, function($value) use ($firstDayOfWeekTimestamp, $todayTimestamp) {
            return is_int($value) && $value >= $firstDayOfWeekTimestamp && $value <= $todayTimestamp;
        });

        echo 'Vecka '.$firstDayOfWeek->format('W').': '.count($thisWeekLogins).'<br>';

        // Get timestamp for first day of current month
        $firstDayOfMonth = new DateTime('first day of this month', new DateTimeZone('Europe/Stockholm'));
        $firstDayOfMonthTimestamp = $firstDayOfMonth->setTime(0, 0, 0)->getTimestamp();

        // Filter logins from current month
        $thisMonthLogins = array_filter($lastloginDayTimestamps, function($value) use ($firstDayOfMonthTimestamp, $todayTimestamp) {
            return is_int($value) && $value >= $firstDayOfMonthTimestamp && $value <= $todayTimestamp;
        });

        echo strftime('%B', $firstDayOfMonth->getTimestamp()).': '.count($thisMonthLogins).'<br>';

        // Get timestamp for first day of current year
        $firstDayOfYear = new DateTime('first day of this year', new DateTimeZone('Europe/Stockholm'));
        $firstDayOfYearTimestamp = $firstDayOfYear->setTime(0, 0, 0)->getTimestamp();

        // Filter logins from current year
        $thisYearLogins = array_filter($lastloginDayTimestamps, function($value) use ($firstDayOfYearTimestamp, $todayTimestamp) {
            return is_int($value) && $value >= $firstDayOfYearTimestamp && $value <= $todayTimestamp;
        });

        echo strftime('%Y', $firstDayOfYear->getTimestamp()).': '.count($thisYearLogins).'<br>';

        // Get timestamp for one week ago
        $oneWeekAgo = new DateTime('today -1 week', new DateTimeZone('Europe/Stockholm'));
        $oneWeekAgoTimestamp = $oneWeekAgo->setTime(0, 0, 0)->getTimestamp();

        // Filter logins from one week back
        $oneWeekLogins = array_filter($lastloginDayTimestamps, function($value) use ($oneWeekAgoTimestamp, $todayTimestamp) {
            return is_int($value) && $value >= $oneWeekAgoTimestamp && $value <= $todayTimestamp;
        });

        echo 'Sedan '.$oneWeekAgo->format('Y-m-d').': '.count($oneWeekLogins).'<br>';

        // Get timestamp for one month ago
        $oneMonthAgo = new DateTime('today -1 month', new DateTimeZone('Europe/Stockholm'));
        $oneMonthAgoTimestamp = $oneMonthAgo->setTime(0, 0, 0)->getTimestamp();

        // Filter logins from one month back
        $oneMonthLogins = array_filter($lastloginDayTimestamps, function($value) use ($oneMonthAgoTimestamp, $todayTimestamp) {
            return is_int($value) && $value >= $oneMonthAgoTimestamp && $value <= $todayTimestamp;
        });

        echo 'Sedan '.$oneMonthAgo->format('Y-m-d').': '.count($oneMonthLogins).'<br>';

        // Get timestamp for one year ago
        $oneYearAgo = new DateTime('today -1 year', new DateTimeZone('Europe/Stockholm'));
        $oneYearAgoTimestamp = $oneYearAgo->setTime(0, 0, 0)->getTimestamp();

        // Filter logins from one year back
        $oneYearLogins = array_filter($lastloginDayTimestamps, function($value) use ($oneYearAgoTimestamp, $todayTimestamp) {
            return is_int($value) && $value >= $oneYearAgoTimestamp && $value <= $todayTimestamp;
        });

        echo 'Sedan '.$oneYearAgo->format('Y-m-d').': '.count($oneYearLogins).'<br>';
    }