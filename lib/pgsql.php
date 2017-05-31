<?php
/**
 * Copyright (c) 2014 Jakob Nixdorf <flocke@shadowice.org>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * User authentication against an external PostgreSQL database.
 *
 * @category Apps
 * @package  UserExternalPgSQL
 * @author   Jakob Nixdorf <flocke@shadowice.org>
 * @license  http://www.gnu.org/licenses/agpl AGPL
 */

use \OCA\user_external;

class OC_User_PgSQL extends \OCA\user_external\Base {
    private $host;
    private $username;
    private $password;
    private $dbname;
    private $pwquery;
    private $displaynamequery;

    /**
     * Create new PostgreSQL authentication provider
     *
     * @param string  $host       Hostname or IP of PostgreSQL server
     * @param string  $username   Username for the PostgreSQL server
     * @param string  $password   Password for the PostgreSQL server
     * @param string  $dbname     Database with the user information
     * @param string  $pwquery    Query used to fetch the password (%u is replaced by the uid)
     */
    public function __construct($host, $username, $password, $dbname, $pwquery, $displaynamequery = null) {
        parent::__construct($host, $username, $password, $dbname, $pwquery);
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->dbname = $dbname;
        $this->pwquery = $pwquery;
        $this->displaynamequery = $displaynamequery;
    }

    private function simple_pgsql_query($dbconn, $query, $row = 0, $col = 0) {
        if ( ! $result = pg_query($dbconn, $query) ) {
            OCP\Util::writeLog('user_external_pgsql', sprintf("PostgreSQL query '%s' failed", $query), OCP\Util::ERROR);
            return null;
        }

        if ( pg_num_rows($result) > 0 ) {
          if (! $value = pg_fetch_result($result, $row, $col)) {
            OCP\Util::writeLog('user_external_pgsql', sprintf("Unable to fetch result (row = %d, col = %d) of PostgreSQL query '%s'", $row, $col, $query), OCP\Util::ERROR);
            $value = null;
          }
        } else {
          $value = null;
        }

        pg_free_result($result);
        return $value;
    }

    /**
     * Check if the password is correct without logging in the user
     *
     * @param string $uid       The username
     * @param string $password  The password
     *
     * @return the username or false
     */
    public function checkPassword($uid, $password) {
        if ( ! $dbconn = pg_connect("host=" . $this->host . " user=" . $this->username . " password=" . $this->password . " dbname=" . $this->dbname) ) {
            OCP\Util::writeLog( 'user_external_pgsql', 'ERROR: Could not connect to the PostgreSQL database', OCP\Util::ERROR);
            return false;
        };

        $pwquery = str_replace('%u', $uid, $this->pwquery);
        $fetched_pw = $this->simple_pgsql_query($dbconn, $pwquery);

        if (! is_null($this->displaynamequery)) {
            $dnquery = str_replace('%u', $uid, $this->displaynamequery);
            $displayname = $this->simple_pgsql_query($dbconn, $dnquery);
        } else {
            $displayname = null;
        }

        pg_close($dbconn);

        if( crypt($password, $fetched_pw) == $fetched_pw ) {
            $new_user = ! $this->userExists($uid);
            
            $this->storeUser($uid);

            if ($new_user and ! is_null($displayname)) {
                $this->setDisplayName($uid, $displayname);
            }

            return $uid;
        } else {
            return false;
        }
    }
}
