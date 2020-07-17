<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Inform our users that Google Chrome is reporting false positives.
 */
?>
@js('media://com_ars/js/ChromeMessage.min.js', $this->getContainer()->mediaVersion, 'text/javascript', true)

@section('chromemessage')
    <div class="akeeba-panel--green chromeFalsePositive">
        <header class="akeeba-block-header">
            <h2>Google Chrome displays false positives for some downloads</h2>
        </header>
        <div>
            <p>We are aware that Google Chrome is reporting some of our downloads as unsafe or uncommon software. This
                is a false positive, i.e. a mistake on Chrome's part. Here is how to verify our software is
                legitimate:</p>
            <ul>
                <li>Google itself <a
                            href="https://transparencyreport.google.com/safe-browsing/search?url=https:%2F%2Fwww.akeebabackup.com&hl=en">reports
                        no unsafe content</a> on our site.
                </li>
                <li>Submit the files you download from our site to <a href="https://www.virustotal.com">VirusTotal</a>
                    where they will be scanned by all virus scanners and come up clean and safe to use.
                </li>
            </ul>
            <p>
                Unfortunately, Google will refuse to download the files it mistakenly believes should be blocked. You
                will need to use a different browser. We strongly recommend using Mozilla Firefox instead.
            </p>
            <p>
                We apologise for the inconvenience. Unfortunately, this issue is not under our control &ndash; Google
                does not offer a way to report false positives.
            </p>
        </div>
    </div>
@stop

<div id="chromeFalsePositives" style="display: none">
    @yield('chromemessage')
</div>

<noscript>
    @yield('chromemessage')
</noscript>