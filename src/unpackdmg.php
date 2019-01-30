<?php
/**
 * UnpackDMG Class and command line tool.
 */

require_once( __DIR__ . '/../vendor/autoload.php' );
class UnpackDMG {
  public $version = "1.0.0"; // TODO: obtain via composer
  public $climate = NULL;
  public $mountName = NULL;
  public $folderName = NULL;
  
  /**
   * Create our UnpackDMG object
   */
  function __construct() {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
      echo 'Windows is not supported.';
      exit();
    }
  }

  /**
   * Process the command line interface arguments
   */
  function cli() {
    $composer = json_decode(file_get_contents(__DIR__ . "/../composer.json"));
    $this->climate = new League\CLImate\CLImate;
    $this->climate->description( $composer->description . "\nVersion " . $this->version);
    $this->climate->arguments->add([
      'help' => [
        'prefix'       => '?',
        'longPrefix'   => 'help',
        'description'  => 'print this help',
        'noValue'      => true,
      ],
      'directory' => [
        'prefix'       => 'd',
        'longPrefix'   => 'directory',
        'description'  => 'directory path & name to unpack to (default .dmg filename)',
        'defaultValue' => '',
      ],
      'quiet' => [
        'prefix'       => 'q',
        'longPrefix'   => 'quiet',
        'description'  => 'quiet (no output)',
        'noValue'      => true
      ],
      'version' => [
        'prefix'       => 'v',
        'longPrefix'   => 'version',
        'description'  => 'output version number',
        'noValue'      => true,
      ],
      'filename' => [
        'description'  => 'the macOS .dmg file to unpack'
      ]
    ]);
    $this->climate->arguments->parse();
    if (! $this->climate->arguments->defined("help")) {
      $this->showVersion();
      $this->mount();
      $this->unpack();
      $this->unmount();
    }
    $this->climate->usage();
  }

  /**
   * Mount the dmg file and obtain the mount name and folder name
   */
  function mount() {
    $filename = $this->climate->arguments->get('filename');
    if (NULL == $filename) {
      echo "Error, missing .dmg file: $filename\nType 'unpackjson --help' for more options.\n";
      exit();
    }else{
      if (FALSE === file_exists($filename)) {
        $filename = getcwd() . "/$filename";
        if (FALSE === file_exists($filename)) {
          echo "Error, missing .dmg file: $filename\nType 'unpackjson --help' for more options.\n";
          exit();
        }
      }
    }
    if (! $this->climate->arguments->defined('quiet')) {
      echo "Mounting dmg...\n";
    }
    exec("hdiutil attach -noverify \"$filename\"", $r);
    $r = new steveorevo\GString(array_values(array_slice($r, -1))[0]);
    $this->folderName = $r->getRightMost("    ")->trim()->__toString();
    $this->mountName = $r->getLeftMost("    ")->trim()->__toString();
  }

  /**
   * Copy a file, or recursively copy a folder and its contents (adapted from Aidan Lister)
   * 
   * @author      Aidan Lister <aidan@php.net>
   * @version     1.0.1
   * @link        http://aidanlister.com/2004/04/recursively-copying-directories-in-php/
   * @param       string   $source    Source path
   * @param       string   $dest      Destination path
   * @param       int      $permissions New folder creation permissions
   * @return      bool     Returns true on success, false on failure
   */
  function xcopy($source, $dest, $permissions = 0755) {
    if (! $this->climate->arguments->defined('quiet')) {
      echo "Copying $source to $dest\n";
    }

    // Check for symlinks
    if (is_link($source)) {
        return symlink(readlink($source), $dest);
    }

    // Simple copy for a file
    if (is_file($source)) {
        return copy($source, $dest);
    }

    // Make destination directory
    if (!is_dir($dest)) {
        mkdir($dest, $permissions);
    }

    // Loop through the folder
    $dir = dir($source);
    while (false !== $entry = $dir->read()) {
        // Skip pointers
        if ($entry == '.' || $entry == '..') {
            continue;
        }

        // Deep copy directories
        $this->xcopy("$source/$entry", "$dest/$entry", $permissions);
    }

    // Clean up
    $dir->close();
    return true;
  }

  /**
   * Unpack dmg content to named folder
   */
  function unpack() {
    $f = new steveorevo\GString($this->climate->arguments->get('filename'));
    $f = $f->getRightMost("/")->delRightMost(".")->__toString();
    $directory = $this->climate->arguments->get('directory');
    if (NULL == $directory) {
      $directory = getcwd() . "/$f";
    }

    // Create directory if it does not exist
    if (FALSE === file_exists($directory)) {
      mkdir($directory);
    }else{
      if (is_file($directory)) {
        mkdir($directory);
      }
    }
    if (FALSE === file_exists($directory)) {
      if (! $this->climate->arguments->defined('quiet')) {
        echo("Unable to create output folder $directory");
        return;
      }
    }

    // Copy dmg content to given directory
    if (! $this->climate->arguments->defined('quiet')) {
      echo "Copying files and folders...\n";
    }
    $this->xcopy($this->folderName, $directory);
  }

  /**
   * Unmount the dmg file.
   */
  function unmount() {
    if (! $this->climate->arguments->defined('quiet')) {
      echo "Unmounting dmg...\n";
    }
    exec("hdiutil detach $this->mountName", $r);
    exit();
  }

  /**
   * Show the version number
   */
  function showVersion() {
    if (! $this->climate->arguments->defined('version')) return;
    echo "UnpackDMG version " . $this->version . "\n";
    echo "Copyright ©2019 Stephen J. Carnam\n";
    exit();
  }
}

// From command line, create instance & do cli arguments
if ( PHP_SAPI === 'cli' ) {
  $myCmd = new UnpackDMG();
  $name = new Steveorevo\GString(__FILE__);
  $argv[0] = $name->getRightMost("/")->delRightMost(".");
  $myCmd->cli();
}
