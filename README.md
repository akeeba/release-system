# Akeeba Release System

A download manager component for Joomla!, designed for software distribution

## NO SUPPORT

This software is provided **WITHOUT ANY KIND OF SUPPORT WHATSOEVER**. We have disabled Issues on this repository to stress this. Kindly note that any requests sent to us about this software will not be replied.
 
## THIS REPOSITORY IS FOR DEVELOPERS ONLY

No installable ZIP packages will be provided for this software since March 2016. You can build one from the source following the developer instructions in this README.

## INTERNAL PROJECT

This is meant to be an internal software development project for use with our site, akeebabackup.com. As such, future versions of this software will drop any features we do not wish to maintain because we do not intend or anticipate to use on our site.

## Prerequisites

In order to build the installation packages of this component you will need to have the following tools:

* A command line environment. Using Bash under Linux / Mac OS X works best. On Windows you will need to run most tools through an elevated privileges (administrator) command prompt on an NTFS filesystem due to the use of symlinks. Press WIN-X and click on "Command Prompt (Admin)" to launch an elevated command prompt.
* A PHP CLI binary in your path
* Command line Git executables
* PEAR and Phing installed, with the Net_FTP and VersionControl_SVN PEAR packages installed
* (Optional) libxml and libsxlt command-line tools, only if you intend on building the documentation PDF files

You will also need the following path structure inside a folder on your system

* **ars** This repository
* **buildfiles** [Akeeba Build Tools](https://github.com/akeeba/buildfiles)
* **fof3** [Framework on Framework 3.x](https://github.com/akeeba/fof)

You will need to use the exact folder names specified here.

### Initialising the repository

All of the following commands are to be run from the MAIN directory. Lines
starting with $ indicate a Mac OS X / Linux / other *NIX system commands. Lines
starting with > indicate Windows commands. The starting character ($ or >) MUST
NOT be typed!

1. You will first need to do the initial link with Akeeba Build Tools, running
   the following command (Mac OS X, Linux, other *NIX systems):

		$ php ../buildfiles/tools/link.php `pwd`

   or, on Windows:

		> php ../buildfiles/tools/link.php %CD%

1. After the initial linking takes place, go inside the build directory:

		$ cd build

   and run the link phing task:

		$ phing link

### Useful Phing tasks

All of the following commands are to be run from the MAIN/build directory.
Lines starting with $ indicate a Mac OS X / Linux / other *NIX system commands.
Lines starting with > indicate Windows commands. The starting character ($ or >)
MUST NOT be typed!

You are advised to NOT distribute the library installation packages you have built yourselves with your components. It
is best to only use the official library packages released by Akeeba Ltd.

1. Relinking internal files

   This is only required when the buildfiles change.

		$ phing link
		> phing link

1. Creating a dev release installation package

   This creates the installable ZIP packages of the component inside the
   MAIN/release directory.

		$ phing git
		> phing git
		
   **WARNING** Do not distribute the dev releases to your clients. Dev releases, unlike regular releases, also use a
   dev version of FOF 3.

1. Build the documentation in PDF format

   This creates the documentation in PDF format

		$ phing doc-j-pdf
		> phing doc-j-pdf


Please note that all generated files (ZIP library packages, PDF files, HTML files) are written to the
`release` directory inside the repository's root.
