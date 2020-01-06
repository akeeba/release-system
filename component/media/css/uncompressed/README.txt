================================================================================
Akeeba Release System
================================================================================
Source SCSS Files

This folder contains the source SCSS files which get compiled into the minified
(.min.css) files that Akeeba Release System uses to display itself.

DO NOT MODIFY THESE FILES DIRECTLY; they will have no effect.

We recommend that you copy them somewhere on your own computer, edit them as
necessary and then use an SCSS compiler application, such as Koala, to compile
them into the minified (.min.css) files. You can use the compiled files for
template media overrides per the documentation.

Below you'll find a quick reference of which file goes where and what does it
do.

Backend CSS Files
--------------------------------------------------------------------------------

These files go to administrator/templates/ADMIN_TEMPLATE/html/com_ars/css where
ADMIN_TEMPLATE is the folder name that contains your site's administrator
template. It is usually isis (the default Joomla 3 template) or atum (the
default Joomla 4 template).

backend.min.css      -- Main file with all the regular styling
backend_dark.min.css -- Dark Mode CSS file, only overrides colors

Frontend CSS Files
--------------------------------------------------------------------------------

These files go to templates/SITE_TEMPLATE/html/com_ars/css where SITE_TEMPLATE
is the folder name that contains your public site's template. This depends on
your site.

frontend.min.css      -- Main file with all the regular styling
frontend_dark.min.css -- Dark Mode CSS files, only overrides colors

SCSS organization
--------------------------------------------------------------------------------

The SCSS files in the root of this folder are pretty "empty". They are meant to
provide an overview of what is going on.

The "sources" folder has the SCSS files imported by the root files. These do
all the work and they are organized by the interface concept they address.

The "_variables.scss" file contains the color theme. All colors used throughout
the SCSS are derived from these base colors by darkening or lightening them. If
you only want to change the color scheme this the only thing you need to touch.
