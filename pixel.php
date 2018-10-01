<?php
// Tracking pixel script in php, with explanations.
//
// Donations: http://riceball.com/donate
//
// Set database to a file your server can access.
$database = "/tmp/tracking.sqlite";

/*
 * PHP sessions set the PHPSESSIONID cookie on the browser, and, using that
 * value, creates a corresponding file on the server, which contains the values
 * accessible in the $_SESSION array.  This file is loaded whenever the session
 * is started.  It's saved at the end of the script.
 */

session_start();

/*
 * We use a random number to identify a user.
 */

if (! isset($_SESSION['user'])) {
    $_SESSION['user'] = rand(1, 9999999);
}

/*
 * Pixels have the form <img src="http://example.com/pixel.php?e=whatever" />
 *
 * The 'e' paramter stands for "event", and whenever the browser loads the
 * graphic, it passes the value of e along with the request.
 *
 * The value of e must be only lowercase letters and numbers.  If it's
 * missing, e will be the empty string.
 */

$e = filter_input( INPUT_GET, 'e', FILTER_VALIDATE_REGEXP, 
        ['default'=>'', 'regexp'=>'/^[a-z0-9]*$/']);

/*
 * We store our data in a sqlite3 file. Not the ideal database for this, 
 * but it's good enough for a demo.
 *
 * SQLite3 presents an object oriented interface. For each database,
 * you create a subclass of SQLite3, and associate it with a database.
 */

class DB extends SQLite3 {
    function __construct() {
        global $database;
        $this->open($database);
        $this->enableExceptions(true);
        try {
            $this->query('CREATE TABLE events (user NUMERIC, e TEXT)');
        } catch(Exception $e) {
            /*
             * This soaks up the expected exception.
             */
            if ('table events already exists' != $e->getMessage()) {
                echo $e;
            }
        }
    }

    /*
     * The object oriented style of programming allows you
     * to encapsulate code that alters the database within
     * the class, so you can interact with the database
     * through methods.
     */

    public function insert($user, $e) {
        $stmt = $this->prepare('INSERT INTO events (user, e) VALUES (:user, :e)');
        $stmt->bindValue(':user', $user);
        $stmt->bindValue(':e', $e);
        $stmt->execute();
    }
}

/*
 * We now store the user id and event in the database.
 * In a real tracker, we would save out additional browser information,
 * time, referrer, and other information.
 */

$db = new DB();
$db->insert( $_SESSION['user'], $e );

/*
 * Now, we pretend to be a graphic file that is a 1 pixel GIF.
 */

header('Content-type: image/gif');

/*
 * Instead of creating a graphic file, or reading one from disk, we just embed
 * the file as data, right into the script.
 */

echo base64_decode('R0lGODlhAQABAIAAAP///////yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==');
