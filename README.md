unpackdmg
==========

Unpacks the content of a macOS .dmg file to a given folder of the same name.

### About

This command line tool will temporarily mount a macOS .dmg file and copy its content to a folder of the same name followed by unmounting the given .dmg file. 

#### Help
Type unpackdmg --help

```
UnpackDMG is a macOS .dmg file extraction tool.
Version 1.0.0

Usage: unpackdmg [-?, --help] [-d directory, --directory directory] [-q quiet, --quiet quiet]

Optional Arguments:
	-?, --help
		print this help
	-d directory, --directory directory
		directory path & folder name to unpack to (default .dmg filename)
	-q quiet, --quiet quiet
		quiet (no output)
	-v, --version
		output version number
	filename
		the macOS .dmg filename to unpack
```
  
