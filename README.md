# Nextcloud Mail

**External PostgreSQL authentication for [Nextcloud](https://nextcloud.com)**

## Installation

Just download/clone this GitHub repository into the apps folder of you Nextcloud installation.

## Configuration

For this app to work the [user_external](https://github.com/nextcloud/apps/tree/master/user_external) app (included in Nextcloud) has to be enabled as well.
This can be done by using the internal Apps menu in your Nextcloud installation or with the [`occ` command](https://docs.nextcloud.com/server/12/admin_manual/configuration_server/occ_command.html):

```bash
occ app:enable user_external
occ app:enable user_external_pgsql
```

After both apps are enabled you have to add the database configuration to your _config.php_:

```php
'user_backends' => array (
    0 => array (
        'class' => 'OC_User_PgSQL',
        'arguments' => array (
            0 => 'hostname',
            1 => 'username',
            2 => 'password',
            3 => 'database',
            4 => 'password_query',
            5 => 'displayname_query',
        ),
    ),
),
```

### Parameters

0. `hostname` **(required)**: Hostname of the PostgreSQL server
1. `username` **(required)**: Username of the PostgreSQL user
2. `password` **(required)**: Password of the PostgreSQL user
3. `database` **(required)**: Name of the PostgreSQL database
4. `password_query` **(required)**: PostgreSQL query for getting the password of one user. You have to use `%u` as placeholder for the username. Example: `SELECT password FROM users WHERE username='%u'`
5. `displayname_query` *(optional)*: PostgreSQL query for getting the displayname of one user. You have to use `%u` as placeholder for the username. Example `SELECT fullname FROM users WHERE username='%u'`

### Displayname

In addition to checking a users password this app can also set the displayname of the user on first login.
For this to work you have to set the optional `displayname_query` parameter in the _config.php_ (see above).
This feature is totally optional and is not used as long as you don't specify the query parameter.

## Password format

For now the app requires the passwords to be stored as _crypt()_-hashes in the database.

