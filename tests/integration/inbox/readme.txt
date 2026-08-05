Drop Joomla full packages here to pin an exact build and provision offline, e.g.

    Joomla_5.4.10-Stable-Full_Package.zip
    Joomla_6.0.4-Stable-Full_Package.zip
    Joomla_6.1.2-Stable-Full_Package.zip

run.sh prefers a matching package in this directory over downloading one. When
JOOMLA_VERSION is partial (e.g. "6.1") the highest matching package here wins;
only if none is found does run.sh resolve the latest stable release on that
branch through the Panopticon checksums service and download it into this folder.

The .zip files are not tracked in git — this readme is the only committed file.
