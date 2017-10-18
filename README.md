# Akeeba Release System

A download manager component for Joomla!, designed for software distribution

## What does it do?

Akeeba Release System allows you to distribute software on-line. It is designed to make it easy to provide Joomla! software, generating the necessary XML update stream. 

## Download

Pre-built packages of Akeeba Release System will be made available through [our GitHub repository's Releases page](https://github.com/akeeba/release-system/releases) starting with version 3.2.1.

We do understand the irony in not using itself to distribute it but it's a matter of cost, not a matter of capability. Akeeba Release System is [currently used to distribute Joomla! itself](https://downloads.joomla.org/) and [all of our commercial software](https://www.akeebabackup.com/download.html), so it's safe to say that it's pretty darned robust. 

## No support - For developers only

This software is provided **WITHOUT ANY KIND OF SUPPORT WHATSOEVER**. You are free to consult [the documentation](https://github.com/akeeba/release-system/wiki).

If you are a developer you are free to submit a pull request with your code fix, as long as there is a clear description of what was not working for you, why and how you fixed it. 
 
## Prerequisites

In order to build the installation packages of this component you will need to have the following tools:

* A command line environment. Using Bash under Linux / Mac OS X works best. On Windows you will need to run most tools through an elevated privileges (administrator) command prompt on an NTFS filesystem due to the use of symlinks. Press WIN-X and click on "Command Prompt (Admin)" to launch an elevated command prompt.
* A PHP CLI binary in your path
* Command line Git executables
* Phing
* (Optional) libxml and libsxlt command-line tools, only if you intend on building the documentation PDF files

You will also need the following path structure inside a folder on your system

* **ars** This repository
* **buildfiles** [Akeeba Build Tools](https://github.com/akeeba/buildfiles)
* **fof3** [Framework on Framework 3.x](https://github.com/akeeba/fof)
* **translations** [Akeeba Translations](https://github.com/akeeba/translations)

You will need to use the exact folder names specified here.

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