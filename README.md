# rimsec
A small IP BAN manager.

Drunk-coded.

## Features
- Auto IP BAN
- Auto IP BAN If you make an exact amount of tries in a period of time.
- Supports temprorary and perm BANs.
- Different BAN templates. Easy to integrate.
- Session based cache system. So It won't check the database Constantly.
- Detects IP changes, and disables the cache automatically.
- Freepass system; So desired/whitelisted IPs won be blocked, even if BAN method called.
- Freepass system also bypass the Db checks, so regular users won't affect the system and MySQL performance.
- Powered with MySQL with PDO.
- Protected against SQL Injections.
- Also system supports multi-site BAN management with an IP pool from one MySQL Database.
- Freepass also prevents blocking the whitlisted IPs from their websites, even if a whitelisted IP is blocked from other website, the IP is still cleared to enter it's own website.
- Easy to use.
- Detailed debug log support. You can see the debug logs if you want.
- IP Priority system.
    - With this, you can ban different IPs from different pages of your sites.
    - Like, If IP is banned with 5 priority from a site, that IP still can visit a page that requires 10 priority ban.

## How to Use
- Need to create a MySQL Database and import the ip_details.sql. System uses ip_details table to check BANs, and also add BANs.
- Include class.rimsec.php file
```
    require 'class.rimsec.php';
```

- Call the class with settings
```
    $rimsec = new rimsec(
        [
            'mysqlInfo' => [
                'host' => 'localhost', 
                'dbase' => '', 
                'user' => '', 
                'pass' => ''
            ],
            'freepass' => [
                '127.0.0.1'
            ],
            'sessions' => true
        ]
    );
```

- Use **freepass** to whitelist IPs.  
- Use **sessions** to enable or disable session cache support.
    - Remember, you need to use sessions to count tries, otherwise, system will BAN immediately after you call BAN method

## Rimsec Settings
Settings in the class file are easy to understand.  

## Templates
You can use simple template engine to show your BAN messages. There are two options;  
- HTML/File Format

```
    $rimsec->template('templatepath.html', 'html');
```
- Base64 Format

```
    $rimsec->template('BASE64 ENCODED FILE TEMPLATE', 'base');
```

You should call this method before checking or adding BANs.  

### Template Variables
You can use these template variables in both of your templates. 
**{ip}** - Banned user IP
**{reason}** - Ban reason
**{banend}** - Ban ending time
**{created}** - Ban creation time

## Priority System
Every IP ban has also a priority value. If this priority value is smaller than the priority given to the banCheck() method, IP is allowed to visit that site.


## Check bans
You can check bans easily with one method. This method also support *priority* parameter.

Default value is 0 for *priority* so It will block all banned IPs from the system.

```
    $priority = 0;
    $rimsec->checkBan($priority);
```   

This method will block all IPs that has bigger priority values than *$priority* value.

Also, if debug logs enabled, user still can see the debug logs even if blocked from the system.

## Add bans
You can easily add bans to the system with this method. Default parameters are set, so If you don't pass any details; IP will be banned permanently.
```
    $priority = 10;
    $permanent = 1; // True or False, (1/0)
    $banend = "2019-12-11 10:00:00";
    $reason = "No reason";
    $rimsec->addBan($priority, $permanent, $banend , $reason);
```
**$priority** - Priority value of IP, higher is better.  
**$banend** - Ban end time. You can set a DATETIME value or just write +1 week, +1 day etc. more details [here](https://www.php.net/manual/tr/function.strtotime.php).  
**$permanent** - Is it a Permanent BAN? If it is True or 1; system will NOT look the *$banend* value.  
**$reason** - BAN reason.  

These values will be available and converted to the all templates automatically.

# Credit  
Coded by Evrim Altay KOLUAÇIK   25-26/12/2019   OD/UA  
https://evrimaltay.net   
https://rimtay.com   

**Use at your own risk.**