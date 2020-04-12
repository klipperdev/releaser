Get starting
============

## Requirements

This tool required:

- [PHP 7.4](https://www.php.net) or greater
- [Git](https://git-scm.com)
- [Splitsh-lite](https://github.com/splitsh/lite) (to replace Git subtree, optional)

## Installation

Download the phar from the release page:

https://github.com/klipperdev/releaser/releases

**For linux/mac:**

Run the commands to install the tool in the `/usr/bin/` directory:

```
mv ./releaser.phar /usr/bin/releaser
chmod a+rx /usr/bin/releaser
```

**For windows:**

Follow the steps to install the tool:

- Copy the `releaser.phar` file in your wished directory like `C:\Bin\Releaser`
- In the same directory of the `releaser.phar` file, create a file `releaser.bat` with the content:
  ```
  @echo off
  php "%~dp0releaser.phar" %*
  ```
- Add the Releaser install directory in the `Path` system environment variable

## Next step

You can read how to:

- [Configure this tool](config.md)
- [Use this tool](usage.md)
