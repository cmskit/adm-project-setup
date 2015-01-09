<?php

require 'inc/htaccess.php';
require '../../../vendor/pclzip/pclzip/pclzip.lib.php';

/**
 * create the directory
 */
mkdir($projectPath);
chmod($projectPath, 0777);

$html = '';

// create a new database

/**
 * @param $type
 * @param $name
 * @param string $user
 * @param string $pass
 * @param string $host
 * @param string $root
 * @param string $root_password
 * @return bool|mixed|string
 */
function createDatabase($type, $name, $user='newuser', $pass='', $host='localhost', $root='root', $root_password='') {

    global $projectPath;
    $errors = '';

    switch($type) {

        case 'mysql':
            try {
                $dbh = new PDO("mysql:host=$host", $root, $root_password);

                $dbh->exec("CREATE DATABASE `$name`;
                    CREATE USER '$user'@'localhost' IDENTIFIED BY '$pass';
                    GRANT ALL ON `$name`.* TO '$user'@'localhost';
                    FLUSH PRIVILEGES;")
                or $errors = print_r($dbh->errorInfo(), true) . "\n";

            } catch (PDOException $e) {
                $errors = 'Database ERROR: ' . $e->getMessage() . "\n";
            }
        break;

        case 'sqlite':

            $db_path = $projectPath . '/objects/' . $name;
            // Create (connect to) SQLite database in file
            $file_db = new PDO('sqlite:' . $db_path);
            // Set errormode to exceptions
            $file_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            //
            chmod($db_path, 0777);

            if(!file_exists($db_path)) {
                $errors = 'could not create SQLite Database ' . $name . "\n";
            }
        break;

    }

    return $errors;
}


/**
 * fix some access-problems of pclzip
 *
 * @param $p_event
 * @param $p_header
 * @return int
 */
function preExtractCallBack($p_event, &$p_header)
{
    $info = pathinfo($p_header['filename']);
    if(!isset($info['extension']))// folders are created here
    {
        $d = $info['dirname'].'/'.$info['filename'];
        @mkdir($d);
        chmod($d, 0777);
        return 0;
    }
    else// files are simply extracted, 
    {
        return 1;
    }
}

/////////////////////////////////////////////////////////////////////////////////////////////////

// if we detect a uploaded project (zip-file), we use this
if(
    $_FILES['file'] && 
    $_FILES['file']['name'] && 
    array_pop(explode('.', strval($_FILES['file']['name']))) == 'zip'
  )
{
    $tname = substr($_FILES['file']['name'], 0, -4);
    $zipPath = $_FILES['file']['tmp_name'];
}
else // we use the empty dummy-project
{
    $tname = 'dummy';
    $zipPath = 'dummy.zip';
}

// we try to extract the ZIP
$archive = new PclZip($zipPath);
if ($archive->extract(  PCLZIP_OPT_PATH, $projectPath,
                        PCLZIP_OPT_REMOVE_PATH, $tname,
                        PCLZIP_CB_PRE_EXTRACT, 'preExtractCallBack',
                        PCLZIP_OPT_SET_CHMOD, 0777
                    ) == 0)
{
    exit('Unrecoverable error "' . $archive->errorName(true) . '"');
} else {
    $c = 0;
    $we_have_some_sqlites = false;

    $error = '';
    foreach($_POST['dbtype'] as $type) {

        // we need to protect SQLite-DB-files from direct access (.htaccess)
        if($type=='sqlite') $we_have_some_sqlites = true;

        $error .= createDatabase(
            $type,
            $_POST['dbname'][$c],
            $_POST['dbuser'][$c],
            $_POST['dbpass'][$c],
            $_POST['dbhost'][$c],
            $_POST['dbrootname'][$c],
             $_POST['dbrootpass'][$c]
        );
        $c++;
    }

}
        
// create code for __configuration.php
$config = '<?php
/**
* Configurations for "'.$_POST['wished_name'].'"
*
* @copyright MIT-License: Free for personal & commercial use. (http://opensource.org/licenses/mit-license.php) 
* @link http://cms-kit.org
* @package '.$_POST['wished_name'].'
*/
namespace '.$_POST['wished_name'].';
final class Configuration
{
    const BUILD                 = \''.$KITVERSION.'\';
    const CRDATE                = \''.date(DATE_RFC822).'\';
    const SECRET                = \''.md5(mt_rand()).'\';

    public static $DB_ALIAS     = array(\''.implode("','", $_POST['dbalias']).'\');
    public static $DB_TYPE      = array(\''.implode("','", $_POST['dbtype']).'\');
    public static $DB_HOST      = array(\''.implode("','", $_POST['dbhost']).'\');
    public static $DB_DATABASE  = array(\''.implode("','", $_POST['dbname']).'\');
    public static $DB_PORT      = array(\''.implode("','", $_POST['dbport']).'\');
    public static $DB_USER      = array(\''.implode("','", $_POST['dbuser']).'\');
    public static $DB_PASSWORD  = array(\''.implode("','", $_POST['dbpass']).'\');
}
?>
';

// create the configuration-file
file_put_contents($projectPath.'/objects/__configuration.php', $config);
chmod($projectPath.'/objects/__configuration.php', 0776);

// create the database-credentials
file_put_contents($projectPath.'/objects/__database.php', str_replace('###PROJECTNAME###', $_POST['wished_name'], file_get_contents($projectPath.'/objects/__database.php')));


// set the session-credentials for the new project
$_SESSION[$_POST['wished_name']]['root'] = 1;
$_SESSION[$_POST['wished_name']]['lang'] = $lang;//browserLang(array('de','en'), 'en');

// create some links
$html .= '<form id="frm">
<fieldset><legend>(4) "'.$_POST['wished_name'].'" '.L('created').'</legend>
<a target="_blank" href="../database_adminer/index.php?project='.$_POST['wished_name'].'">'.L('goto_DB_Admin').' &rArr;</a>
<hr />
<a href="../../index.php?project='.$_POST['wished_name'].'">'.L('goto_Login_Page').' &rArr;</a>
<hr />
<pre>'.$error.'</pre>';

/* do we need this anymore??
$we_have_some_sqlites = false;
for ($i = 0; $i < count($_POST['dbtype']); $i++)
{
    $html .= '<p>' . $_POST['dbalias'][$i];
    
    if($_POST['dbtype'][$i] == 'sqlite' && !file_exists($projectPath.'/objects/'.$_POST['dbname'][$i]))
    {
        $we_have_some_sqlites = true;
        
        $html .= ' <button type="button" onclick="prompt(\''.L('copy_Database_Path').'\',\''.
                    addslashes(realpath($projectPath.'/objects').DIRECTORY_SEPARATOR.$_POST['dbname'][$i]).
                    '\')">'.L('copy_Database_Path').'</button>  ' . 
                    hlp('sqliteDbPath') . '<hr />';
        
    }
    if($_POST['dbtype'][$i] == 'mysql')
    {
        $html .= ' <button type="button" onclick="prompt(\''.L('copy_Database_Credentials').'\',\'Name: '.$_POST['dbname'][$i].'/Password: '.$_POST['dbpass'][$i].'\')">'.L('copy_Database_Credentials').'</button>';
    }
    
    $html .= '</p>';
}*/

$html .= '
</fieldset>
</form>

</div>
</body>
</html>
';

// build .htaccess-File
buildHtAccess($projectPath, $we_have_some_sqlites);

echo $html;
exit();
?>
