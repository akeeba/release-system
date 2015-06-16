Akeeba Release System
The only download manager made for software distribution.

--------------------------------------------------------------------------------
PREREQUISITES
--------------------------------------------------------------------------------
In order to build the installation packages of this component you need to have
the following tools:

- A command line environment. bash under Linux / Mac OS X works best. On Windows
  you will need to run most tools using an elevated privileges (administrator)
  command prompt.

- The PHP CLI binary in your path

- Command line Subversion and Git binaries(*)

- PEAR and Phing installed, with the Net_FTP and VersionControl_SVN PEAR
  packages installed

- libxml and libxslt tools if you intend to build the documentation PDF files

You will also need the following path structure on your system

- ars			This repository, a.k.a. MAIN directory
- buildfiles	Akeeba Build Tools (https://github.com/akeeba/buildfiles)
- fof			Framework on Framework (https://github.com/akeeba/fof)
- liveupdate	Akeeba Live Update (https://github.com/akeeba/liveupdate)

You will need to use the exact folder names specified here.

--------------------------------------------------------------------------------
INITIALISING THE REPOSITORY
--------------------------------------------------------------------------------

All of the following commands are to be run from the MAIN directory. Lines
starting with $ indicate a Mac OS X / Linux / other *NIX system commands. Lines
starting with > indicate Windows commands. The starting character ($ or >) MUST
NOT be typed!

1. You will first need to do the initial link with Akeeba Build Tools, running
   the following command (Mac OS X, Linux, other *NIX systems):

$ php ../buildfiles/tools/link.php `pwd`

   or, on Windows:

> php ../buildfiles/tools/link.php %CD%

2. After the initial linking takes place, go inside the build directory:

$ cd build

   and run the link phing task:

$ phing link

--------------------------------------------------------------------------------
USEFUL PHING TASKS
--------------------------------------------------------------------------------

All of the following commands are to be run from the MAIN/build directory.
Lines starting with $ indicate a Mac OS X / Linux / other *NIX system commands.
Lines starting with > indicate Windows commands. The starting character ($ or >)
MUST NOT be typed!

1. Symlinking to a Joomla! installation

   This will create symlinks and hardlinks from your working directory to a
   locally installed Joomla! site. Any changes you perform to the repository
   files will be instantly reflected to the site, without the need to deploy
   your changes.

$ phing relink -Dsite=/path/to/site/root
> phing relink -Dsite=c:\path\to\site\root

Examples

$ phing relink -Dsite=/var/www/html/joomla
> phing relink -Dsite=c:\xampp\htdocs\joomla

2. Relinking internal files

   This is required after every major upgrade in the component and/or when new
   plugins and modules are installed. It will create symlinks from the
   various external repositories to the MAIN directory.

$ phing link
> phing link

3. Creating a dev release installation package

   This creates the installable ZIP packages of the component inside the
   MAIN/release directory.

$ phing git
> phing git

4. Build the documentation in PDF format

$ phing documentation
> phing documentation
