<?php

namespace Foolz\Package;

/**
 * Automates loading of plugins
 *
 * @author   Foolz <support@foolz.us>
 * @package  Foolz\Package
 * @license  http://www.apache.org/licenses/LICENSE-2.0.html Apache License 2.0
 */
class Loader
{
	/**
	 * The type of package in use. Can be in example 'theme' or 'plugin'
	 * Override this to change type of package
	 *
	 * @var  string
	 */
	protected $type_name = 'package';

	/**
	 * The class into which the resulting objects are created.
	 * Override this, in example Foolz\Plugin\Plugin or Foolz\Theme\Theme
	 *
	 * @var  string
	 */
	protected $type_class = 'Foolz\Package\Package';

	/**
	 * The instances of the Loader class
	 *
	 * @var  \Foolz\Package\Loader[]
	 */
	protected static $instances = [];

	/**
	 * The dirs in which to look for packages
	 *
	 * @var  null|array
	 */
	protected $dirs = null;

	/**
	 * The classes for the autoloader
	 *
	 * @var  null|array
	 */
	protected $classes = null;

	/**
	 * The packages found
	 *
	 * @var  null|array  as first key the dir name, as second key the slug
	 */
	protected $packages = null;

	/**
	 * Tells if packages should be reloaded
	 *
	 * @var  boolean
	 */
	protected $reload = true;

	/**
	 * Construct registers the class to the autoloader
	 */
	public function __construct()
	{
		$this->register();
	}

	/**
	 * Creates or returns a named instance of Loader
	 *
	 * @param   string  $instance  The name of the instance to use or create
	 * @param   bool    $prepend   If the autoloader should be prepended
	 *
	 * @return  \Foolz\Package\Loader
	 */
	public static function forge($instance = 'default', $prepend = false)
	{
		if ( ! isset(static::$instances[$instance]))
		{
			return static::$instances[$instance] = new static($prepend);
		}

		return static::$instances[$instance];
	}

	/**
	 * Destroys a named instance and unregisters its autoloader
	 *
	 * @param  string  $instance  The name of the instance to use or create
	 */
	public static function destroy($instance = 'default')
	{
		$obj = static::$instances[$instance];
		$obj->unregister();
		unset(static::$instances[$instance]);
	}

	/**
	 * Registers the current object with spl_autoload_register
	 *
	 * @param  bool  $prepend  if the class loader should run first, would allow overriding classes
	 */
	protected function register($prepend = false)
	{
		spl_autoload_register([$this, 'classLoader'], true, $prepend);
	}

	/**
	 * Unregisters the current object with spl_autoload_unregister
	 */
	protected function unregister()
	{
		spl_autoload_unregister([$this, 'classLoader']);
	}

	/**
	 * Class Autoloader function
	 *
	 * @param   string  $class  The name of the class to load
	 *
	 * @return  void|boolean  True if the class has been found, void otherwise
	 */
	public function classLoader($class, $psr = false)
	{
		if (isset($this->classes[$class]))
		{
			include $this->classes[$class];
			return true;
		}
	}

	/**
	 * Adds a class to the autoloader
	 *
	 * @param   string  $class  The name of the class
	 * @param   string  $path   The path where the class can be found
	 *
	 * @return  \Foolz\Package\Loader  The current object
	 */
	public function addClass($class, $path)
	{
		$this->classes[$class] = $path;
		return $this;
	}

	/**
	 * Returns the path of the class
	 *
	 * @param   string  $class  The name of the class
	 *
	 * @return  string  The path to the class
	 * @throws  \OverflowException  If the class hasn't been declared
	 */
	public function getClassPath($class)
	{
		if ( ! isset($this->classes[$class]))
		{
			throw new \OverflowException;
		}

		return $this->classes[$class];
	}

	/**
	 * Removes a class from the autoloader
	 *
	 * @param   string  $class  The class to remove
	 *
	 * @return  \Foolz\Package\Loader  The current object
	 */
	public function removeClass($class)
	{
		unset($this->classes[$class]);
		return $this;
	}

	/**
	 * Adds a directory to the array of directories to search packages in
	 *
	 * @param   string       $dir_name  If $dir is not set this sets both the name and the dir equal
	 * @param   null|string  $dir       The dir where to look for packages
	 *
	 * @return  \Foolz\Package\Loader  The current object
	 * @throws  \DomainException      If the directory is not found
	 */
	public function addDir($dir_name, $dir = null)
	{
		if ($dir === null)
		{
			// if $dir is not specified, we use $dir_name as both $dir and $dir_name
			$dir = $dir_name;
		}

		if ( ! is_dir($dir))
		{
			throw new \DomainException('Directory not found.');
		}

		$this->dirs[$dir_name] = rtrim($dir,'/').'/';

		// set the flag to reload packages on demand
		$this->reload = true;

		return $this;
	}

	/**
	 * Removes a dir from the array of directories to search packages in
	 * Unsets also all the packages in that directory
	 *
	 * @param   string  $dir_name  The named directory
	 *
	 * @return  \Foolz\Package\Loader
	 */
	public function removeDir($dir_name)
	{
		unset($this->dirs[$dir_name]);
		unset($this->packages[$dir_name]);
		return $this;
	}

	/**
	 * Looks for packages in the specified directories and creates the objects
	 */
	public function find()
	{
		if ($this->packages === null)
		{
			$this->packages = array();
		}

		foreach ($this->dirs as $dir_name => $dir)
		{
			if ( ! isset($this->packages[$dir_name]))
			{
				$this->packages[$dir_name] = [];
			}

			$vendor_paths = $this->findDirs($dir);

			foreach ($vendor_paths as $vendor_name => $vendor_path)
			{
				$package_paths = $this->findDirs($vendor_path);

				foreach ($package_paths as $package_name => $package_path)
				{
					if ( ! isset($this->packages[$dir_name][$vendor_name.'/'.$package_name]))
					{
						$package = new $this->type_class($package_path);
						$package->setLoader($this);
						$package->setDirName($dir_name);
						$this->packages[$dir_name][$vendor_name.'/'.$package_name] = $package;
					}
				}
			}
		}
	}

	/**
	 * Internal function to find all directories at the path
	 *
	 * @param   string  $path  The path to look into
	 *
	 * @return  array   The paths with as they the last part of the path
	 */
	protected function findDirs($path)
	{
		$result = array();
		$fp = opendir($path);

		while (false !== ($file = readdir($fp)))
		{
			// Remove '.', '..'
			if (in_array($file, array('.', '..')))
			{
				continue;
			}

			if (is_dir($path.'/'.$file))
			{
				$result[$file] = $path.'/'.$file;
			}
		}

		closedir($fp);

		return $result;
	}

	/**
	 * Gets all the packages or the packages from the directory
	 *
	 * @param   null|string  $dir_name  if specified it gets only a group of packages
	 *
	 * @return  \Foolz\Package\Package[]  All the packages or the packages in the directory
	 * @throws  \OutOfBoundsException   If there isn't such a $dir_name set
	 */
	public function getAll($dir_name = null)
	{
		if ($this->reload === true)
		{
			$this->find();
		}

		if ($dir_name === null)
		{
			return $this->packages;
		}

		if ( ! isset($this->packages[$dir_name]))
		{
			throw new \OutOfBoundsException('There is no such a directory.');
		}

		return $this->packages[$dir_name];
	}

	/**
	 * Gets a single package object
	 *
	 * @param   string  $dir_name           The directory name where to find the package
	 * @param   string  $slug               The slug of the package
	 *
	 * @return  \Foolz\Package\Package
	 * @throws  \OutOfBoundsException  if the package doesn't exist
	 */
	public function get($dir_name, $slug)
	{
		$packages = $this->getAll();

		if ( ! isset($packages[$dir_name][$slug]))
		{
			throw new \OutOfBoundsException('There is no such a package.');
		}

		$packages[$dir_name][$slug]->setDirName($dir_name);
		return $packages[$dir_name][$slug];
	}
}