#!/bin/bash

#
# Internal linking with the language files
#
# -- Component
rm component/language/backend/en-GB
ln -s `pwd`/translations/component/backend/en-GB component/language/backend/en-GB
rm component/language/frontend/en-GB
ln -s `pwd`/translations/component/frontend/en-GB component/language/frontend/en-GB

#
# Link with Live Update
#
rm component/backend/liveupdate/LICENSE.txt
ln -s `pwd`/../liveupdate/code/LICENSE.TXT component/backend/liveupdate/LICENSE.txt
rm component/backend/liveupdate/assets
ln -s `pwd`/../liveupdate/code/assets component/backend/liveupdate/assets
rm component/backend/liveupdate/classes
ln -s `pwd`/../liveupdate/code/classes component/backend/liveupdate/classes
rm component/backend/liveupdate/language
ln -s `pwd`/../liveupdate/code/language component/backend/liveupdate/language
rm component/backend/liveupdate/liveupdate.php
ln `pwd`/../liveupdate/code/liveupdate.php component/backend/liveupdate/liveupdate.php

#
# Link with FOF
#
rm component/fof
ln -s `pwd`/../fof/fof component/fof
