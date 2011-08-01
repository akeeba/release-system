<?php
defined('_JEXEC') or die();

/**
 * This file is based on the Horde_Text_Diff package
 */

/**
 * General API for generating and formatting diffs - the differences between
 * two sequences of strings.
 *
 * The original PHP version of this code was written by Geoffrey T. Dairiki
 * <dairiki@dairiki.org>, and is used/adapted with his permission.
 *
 * Copyright 2004 Geoffrey T. Dairiki <dairiki@dairiki.org>
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-license.php.
 *
 * @package Text_Diff
 * @author  Geoffrey T. Dairiki <dairiki@dairiki.org>
 */
class Horde_Text_Diff
{
    /**
     * Array of changes.
     *
     * @var array
     */
    protected $_edits;

    /**
     * Computes diffs between sequences of strings.
     *
     * @param string $engine     Name of the diffing engine to use.  'auto'
     *                           will automatically select the best.
     * @param array $params      Parameters to pass to the diffing engine.
     *                           Normally an array of two arrays, each
     *                           containing the lines from a file.
     */
    public function __construct($engine, $params)
    {
        if ($engine == 'auto') {
            $engine = extension_loaded('xdiff') ? 'Xdiff' : 'Native';
        } else {
            $engine = ucfirst(basename($engine));
        }

        $class = 'Horde_Text_Diff_Engine_' . $engine;
        $diff_engine = new $class();

        $this->_edits = call_user_func_array(array($diff_engine, 'diff'), $params);
    }

    /**
     * Returns the array of differences.
     */
    public function getDiff()
    {
        return $this->_edits;
    }

    /**
     * returns the number of new (added) lines in a given diff.
     *
     * @since Text_Diff 1.1.0
     *
     * @return integer The number of new lines
     */
    public function countAddedLines()
    {
        $count = 0;
        foreach ($this->_edits as $edit) {
            if ($edit instanceof Horde_Text_Diff_Op_Add ||
                $edit instanceof Horde_Text_Diff_Op_Change) {
                $count += $edit->nfinal();
            }
        }
        return $count;
    }

    /**
     * Returns the number of deleted (removed) lines in a given diff.
     *
     * @since Text_Diff 1.1.0
     *
     * @return integer The number of deleted lines
     */
    public function countDeletedLines()
    {
        $count = 0;
        foreach ($this->_edits as $edit) {
            if ($edit instanceof Horde_Text_Diff_Op_Delete ||
                $edit instanceof Horde_Text_Diff_Op_Change) {
                $count += $edit->norig();
            }
        }
        return $count;
    }

    /**
     * Computes a reversed diff.
     *
     * Example:
     * <code>
     * $diff = new Horde_Text_Diff($lines1, $lines2);
     * $rev = $diff->reverse();
     * </code>
     *
     * @return Horde_Text_Diff  A Diff object representing the inverse of the
     *                    original diff.  Note that we purposely don't return a
     *                    reference here, since this essentially is a clone()
     *                    method.
     */
    public function reverse()
    {
        if (version_compare(zend_version(), '2', '>')) {
            $rev = clone($this);
        } else {
            $rev = $this;
        }
        $rev->_edits = array();
        foreach ($this->_edits as $edit) {
            $rev->_edits[] = $edit->reverse();
        }
        return $rev;
    }

    /**
     * Checks for an empty diff.
     *
     * @return boolean  True if two sequences were identical.
     */
    public function isEmpty()
    {
        foreach ($this->_edits as $edit) {
            if (!($edit instanceof Horde_Text_Diff_Op_Copy)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Computes the length of the Longest Common Subsequence (LCS).
     *
     * This is mostly for diagnostic purposes.
     *
     * @return integer  The length of the LCS.
     */
    public function lcs()
    {
        $lcs = 0;
        foreach ($this->_edits as $edit) {
            if ($edit instanceof Horde_Text_Diff_Op_Copy) {
                $lcs += count($edit->orig);
            }
        }
        return $lcs;
    }

    /**
     * Gets the original set of lines.
     *
     * This reconstructs the $from_lines parameter passed to the constructor.
     *
     * @return array  The original sequence of strings.
     */
    public function getOriginal()
    {
        $lines = array();
        foreach ($this->_edits as $edit) {
            if ($edit->orig) {
                array_splice($lines, count($lines), 0, $edit->orig);
            }
        }
        return $lines;
    }

    /**
     * Gets the final set of lines.
     *
     * This reconstructs the $to_lines parameter passed to the constructor.
     *
     * @return array  The sequence of strings.
     */
    public function getFinal()
    {
        $lines = array();
        foreach ($this->_edits as $edit) {
            if ($edit->final) {
                array_splice($lines, count($lines), 0, $edit->final);
            }
        }
        return $lines;
    }

    /**
     * Removes trailing newlines from a line of text. This is meant to be used
     * with array_walk().
     *
     * @param string $line  The line to trim.
     * @param integer $key  The index of the line in the array. Not used.
     */
    static public function trimNewlines(&$line, $key)
    {
        $line = str_replace(array("\n", "\r"), '', $line);
    }

    /**
     * Checks a diff for validity.
     *
     * This is here only for debugging purposes.
     */
    protected function _check($from_lines, $to_lines)
    {
        if (serialize($from_lines) != serialize($this->getOriginal())) {
            trigger_error("Reconstructed original doesn't match", E_USER_ERROR);
        }
        if (serialize($to_lines) != serialize($this->getFinal())) {
            trigger_error("Reconstructed final doesn't match", E_USER_ERROR);
        }

        $rev = $this->reverse();
        if (serialize($to_lines) != serialize($rev->getOriginal())) {
            trigger_error("Reversed original doesn't match", E_USER_ERROR);
        }
        if (serialize($from_lines) != serialize($rev->getFinal())) {
            trigger_error("Reversed final doesn't match", E_USER_ERROR);
        }

        $prevtype = null;
        foreach ($this->_edits as $edit) {
            if ($prevtype == get_class($edit)) {
                trigger_error("Edit sequence is non-optimal", E_USER_ERROR);
            }
            $prevtype = get_class($edit);
        }

        return true;
    }
}

/**
 * Class used internally by Horde_Text_Diff to actually compute the diffs.
 *
 * This class is implemented using native PHP code.
 *
 * The algorithm used here is mostly lifted from the perl module
 * Algorithm::Diff (version 1.06) by Ned Konz, which is available at:
 * http://www.perl.com/CPAN/authors/id/N/NE/NEDKONZ/Algorithm-Diff-1.06.zip
 *
 * More ideas are taken from: http://www.ics.uci.edu/~eppstein/161/960229.html
 *
 * Some ideas (and a bit of code) are taken from analyze.c, of GNU
 * diffutils-2.7, which can be found at:
 * ftp://gnudist.gnu.org/pub/gnu/diffutils/diffutils-2.7.tar.gz
 *
 * Some ideas (subdivision by NCHUNKS > 2, and some optimizations) are from
 * Geoffrey T. Dairiki <dairiki@dairiki.org>. The original PHP version of this
 * code was written by him, and is used/adapted with his permission.
 *
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-license.php.
 *
 * @author  Geoffrey T. Dairiki <dairiki@dairiki.org>
 * @package Text_Diff
 */
class Horde_Text_Diff_Engine_Native
{
    public function diff($from_lines, $to_lines)
    {
        array_walk($from_lines, array('Horde_Text_Diff', 'trimNewlines'));
        array_walk($to_lines, array('Horde_Text_Diff', 'trimNewlines'));

        $n_from = count($from_lines);
        $n_to = count($to_lines);

        $this->xchanged = $this->ychanged = array();
        $this->xv = $this->yv = array();
        $this->xind = $this->yind = array();
        unset($this->seq);
        unset($this->in_seq);
        unset($this->lcs);

        // Skip leading common lines.
        for ($skip = 0; $skip < $n_from && $skip < $n_to; $skip++) {
            if ($from_lines[$skip] !== $to_lines[$skip]) {
                break;
            }
            $this->xchanged[$skip] = $this->ychanged[$skip] = false;
        }

        // Skip trailing common lines.
        $xi = $n_from; $yi = $n_to;
        for ($endskip = 0; --$xi > $skip && --$yi > $skip; $endskip++) {
            if ($from_lines[$xi] !== $to_lines[$yi]) {
                break;
            }
            $this->xchanged[$xi] = $this->ychanged[$yi] = false;
        }

        // Ignore lines which do not exist in both files.
        for ($xi = $skip; $xi < $n_from - $endskip; $xi++) {
            $xhash[$from_lines[$xi]] = 1;
        }
        for ($yi = $skip; $yi < $n_to - $endskip; $yi++) {
            $line = $to_lines[$yi];
            if (($this->ychanged[$yi] = empty($xhash[$line]))) {
                continue;
            }
            $yhash[$line] = 1;
            $this->yv[] = $line;
            $this->yind[] = $yi;
        }
        for ($xi = $skip; $xi < $n_from - $endskip; $xi++) {
            $line = $from_lines[$xi];
            if (($this->xchanged[$xi] = empty($yhash[$line]))) {
                continue;
            }
            $this->xv[] = $line;
            $this->xind[] = $xi;
        }

        // Find the LCS.
        $this->_compareseq(0, count($this->xv), 0, count($this->yv));

        // Merge edits when possible.
        $this->_shiftBoundaries($from_lines, $this->xchanged, $this->ychanged);
        $this->_shiftBoundaries($to_lines, $this->ychanged, $this->xchanged);

        // Compute the edit operations.
        $edits = array();
        $xi = $yi = 0;
        while ($xi < $n_from || $yi < $n_to) {
            assert($yi < $n_to || $this->xchanged[$xi]);
            assert($xi < $n_from || $this->ychanged[$yi]);

            // Skip matching "snake".
            $copy = array();
            while ($xi < $n_from && $yi < $n_to
                   && !$this->xchanged[$xi] && !$this->ychanged[$yi]) {
                $copy[] = $from_lines[$xi++];
                ++$yi;
            }
            if ($copy) {
                $edits[] = new Horde_Text_Diff_Op_Copy($copy);
            }

            // Find deletes & adds.
            $delete = array();
            while ($xi < $n_from && $this->xchanged[$xi]) {
                $delete[] = $from_lines[$xi++];
            }

            $add = array();
            while ($yi < $n_to && $this->ychanged[$yi]) {
                $add[] = $to_lines[$yi++];
            }

            if ($delete && $add) {
                $edits[] = new Horde_Text_Diff_Op_Change($delete, $add);
            } elseif ($delete) {
                $edits[] = new Horde_Text_Diff_Op_Delete($delete);
            } elseif ($add) {
                $edits[] = new Horde_Text_Diff_Op_Add($add);
            }
        }

        return $edits;
    }

    /**
     * Divides the Largest Common Subsequence (LCS) of the sequences (XOFF,
     * XLIM) and (YOFF, YLIM) into NCHUNKS approximately equally sized
     * segments.
     *
     * Returns (LCS, PTS).  LCS is the length of the LCS. PTS is an array of
     * NCHUNKS+1 (X, Y) indexes giving the diving points between sub
     * sequences.  The first sub-sequence is contained in (X0, X1), (Y0, Y1),
     * the second in (X1, X2), (Y1, Y2) and so on.  Note that (X0, Y0) ==
     * (XOFF, YOFF) and (X[NCHUNKS], Y[NCHUNKS]) == (XLIM, YLIM).
     *
     * This public function assumes that the first lines of the specified portions of
     * the two files do not match, and likewise that the last lines do not
     * match.  The caller must trim matching lines from the beginning and end
     * of the portions it is going to specify.
     */
    protected function _diag ($xoff, $xlim, $yoff, $ylim, $nchunks)
    {
        $flip = false;

        if ($xlim - $xoff > $ylim - $yoff) {
            /* Things seems faster (I'm not sure I understand why) when the
             * shortest sequence is in X. */
            $flip = true;
            list ($xoff, $xlim, $yoff, $ylim)
                = array($yoff, $ylim, $xoff, $xlim);
        }

        if ($flip) {
            for ($i = $ylim - 1; $i >= $yoff; $i--) {
                $ymatches[$this->xv[$i]][] = $i;
            }
        } else {
            for ($i = $ylim - 1; $i >= $yoff; $i--) {
                $ymatches[$this->yv[$i]][] = $i;
            }
        }

        $this->lcs = 0;
        $this->seq[0]= $yoff - 1;
        $this->in_seq = array();
        $ymids[0] = array();

        $numer = $xlim - $xoff + $nchunks - 1;
        $x = $xoff;
        for ($chunk = 0; $chunk < $nchunks; $chunk++) {
            if ($chunk > 0) {
                for ($i = 0; $i <= $this->lcs; $i++) {
                    $ymids[$i][$chunk - 1] = $this->seq[$i];
                }
            }

            $x1 = $xoff + (int)(($numer + ($xlim - $xoff) * $chunk) / $nchunks);
            for (; $x < $x1; $x++) {
                $line = $flip ? $this->yv[$x] : $this->xv[$x];
                if (empty($ymatches[$line])) {
                    continue;
                }
                $matches = $ymatches[$line];
                reset($matches);
                while (list(, $y) = each($matches)) {
                    if (empty($this->in_seq[$y])) {
                        $k = $this->_lcsPos($y);
                        assert($k > 0);
                        $ymids[$k] = $ymids[$k - 1];
                        break;
                    }
                }
                while (list(, $y) = each($matches)) {
                    if ($y > $this->seq[$k - 1]) {
                        assert($y <= $this->seq[$k]);
                        /* Optimization: this is a common case: next match is
                         * just replacing previous match. */
                        $this->in_seq[$this->seq[$k]] = false;
                        $this->seq[$k] = $y;
                        $this->in_seq[$y] = 1;
                    } elseif (empty($this->in_seq[$y])) {
                        $k = $this->_lcsPos($y);
                        assert($k > 0);
                        $ymids[$k] = $ymids[$k - 1];
                    }
                }
            }
        }

        $seps[] = $flip ? array($yoff, $xoff) : array($xoff, $yoff);
        $ymid = $ymids[$this->lcs];
        for ($n = 0; $n < $nchunks - 1; $n++) {
            $x1 = $xoff + (int)(($numer + ($xlim - $xoff) * $n) / $nchunks);
            $y1 = $ymid[$n] + 1;
            $seps[] = $flip ? array($y1, $x1) : array($x1, $y1);
        }
        $seps[] = $flip ? array($ylim, $xlim) : array($xlim, $ylim);

        return array($this->lcs, $seps);
    }

    protected function _lcsPos($ypos)
    {
        $end = $this->lcs;
        if ($end == 0 || $ypos > $this->seq[$end]) {
            $this->seq[++$this->lcs] = $ypos;
            $this->in_seq[$ypos] = 1;
            return $this->lcs;
        }

        $beg = 1;
        while ($beg < $end) {
            $mid = (int)(($beg + $end) / 2);
            if ($ypos > $this->seq[$mid]) {
                $beg = $mid + 1;
            } else {
                $end = $mid;
            }
        }

        assert($ypos != $this->seq[$end]);

        $this->in_seq[$this->seq[$end]] = false;
        $this->seq[$end] = $ypos;
        $this->in_seq[$ypos] = 1;
        return $end;
    }

    /**
     * Finds LCS of two sequences.
     *
     * The results are recorded in the vectors $this->{x,y}changed[], by
     * storing a 1 in the element for each line that is an insertion or
     * deletion (ie. is not in the LCS).
     *
     * The subsequence of file 0 is (XOFF, XLIM) and likewise for file 1.
     *
     * Note that XLIM, YLIM are exclusive bounds.  All line numbers are
     * origin-0 and discarded lines are not counted.
     */
    protected function _compareseq ($xoff, $xlim, $yoff, $ylim)
    {
        /* Slide down the bottom initial diagonal. */
        while ($xoff < $xlim && $yoff < $ylim
               && $this->xv[$xoff] == $this->yv[$yoff]) {
            ++$xoff;
            ++$yoff;
        }

        /* Slide up the top initial diagonal. */
        while ($xlim > $xoff && $ylim > $yoff
               && $this->xv[$xlim - 1] == $this->yv[$ylim - 1]) {
            --$xlim;
            --$ylim;
        }

        if ($xoff == $xlim || $yoff == $ylim) {
            $lcs = 0;
        } else {
            /* This is ad hoc but seems to work well.  $nchunks =
             * sqrt(min($xlim - $xoff, $ylim - $yoff) / 2.5); $nchunks =
             * max(2,min(8,(int)$nchunks)); */
            $nchunks = min(7, $xlim - $xoff, $ylim - $yoff) + 1;
            list($lcs, $seps)
                = $this->_diag($xoff, $xlim, $yoff, $ylim, $nchunks);
        }

        if ($lcs == 0) {
            /* X and Y sequences have no common subsequence: mark all
             * changed. */
            while ($yoff < $ylim) {
                $this->ychanged[$this->yind[$yoff++]] = 1;
            }
            while ($xoff < $xlim) {
                $this->xchanged[$this->xind[$xoff++]] = 1;
            }
        } else {
            /* Use the partitions to split this problem into subproblems. */
            reset($seps);
            $pt1 = $seps[0];
            while ($pt2 = next($seps)) {
                $this->_compareseq ($pt1[0], $pt2[0], $pt1[1], $pt2[1]);
                $pt1 = $pt2;
            }
        }
    }

    /**
     * Adjusts inserts/deletes of identical lines to join changes as much as
     * possible.
     *
     * We do something when a run of changed lines include a line at one end
     * and has an excluded, identical line at the other.  We are free to
     * choose which identical line is included.  `compareseq' usually chooses
     * the one at the beginning, but usually it is cleaner to consider the
     * following identical line to be the "change".
     *
     * This is extracted verbatim from analyze.c (GNU diffutils-2.7).
     */
    protected function _shiftBoundaries($lines, &$changed, $other_changed)
    {
        $i = 0;
        $j = 0;

        assert('count($lines) == count($changed)');
        $len = count($lines);
        $other_len = count($other_changed);

        while (1) {
            /* Scan forward to find the beginning of another run of
             * changes. Also keep track of the corresponding point in the
             * other file.
             *
             * Throughout this code, $i and $j are adjusted together so that
             * the first $i elements of $changed and the first $j elements of
             * $other_changed both contain the same number of zeros (unchanged
             * lines).
             *
             * Furthermore, $j is always kept so that $j == $other_len or
             * $other_changed[$j] == false. */
            while ($j < $other_len && $other_changed[$j]) {
                $j++;
            }

            while ($i < $len && ! $changed[$i]) {
                assert('$j < $other_len && ! $other_changed[$j]');
                $i++; $j++;
                while ($j < $other_len && $other_changed[$j]) {
                    $j++;
                }
            }

            if ($i == $len) {
                break;
            }

            $start = $i;

            /* Find the end of this run of changes. */
            while (++$i < $len && $changed[$i]) {
                continue;
            }

            do {
                /* Record the length of this run of changes, so that we can
                 * later determine whether the run has grown. */
                $runlength = $i - $start;

                /* Move the changed region back, so long as the previous
                 * unchanged line matches the last changed one.  This merges
                 * with previous changed regions. */
                while ($start > 0 && $lines[$start - 1] == $lines[$i - 1]) {
                    $changed[--$start] = 1;
                    $changed[--$i] = false;
                    while ($start > 0 && $changed[$start - 1]) {
                        $start--;
                    }
                    assert('$j > 0');
                    while ($other_changed[--$j]) {
                        continue;
                    }
                    assert('$j >= 0 && !$other_changed[$j]');
                }

                /* Set CORRESPONDING to the end of the changed run, at the
                 * last point where it corresponds to a changed run in the
                 * other file. CORRESPONDING == LEN means no such point has
                 * been found. */
                $corresponding = $j < $other_len ? $i : $len;

                /* Move the changed region forward, so long as the first
                 * changed line matches the following unchanged one.  This
                 * merges with following changed regions.  Do this second, so
                 * that if there are no merges, the changed region is moved
                 * forward as far as possible. */
                while ($i < $len && $lines[$start] == $lines[$i]) {
                    $changed[$start++] = false;
                    $changed[$i++] = 1;
                    while ($i < $len && $changed[$i]) {
                        $i++;
                    }

                    assert('$j < $other_len && ! $other_changed[$j]');
                    $j++;
                    if ($j < $other_len && $other_changed[$j]) {
                        $corresponding = $i;
                        while ($j < $other_len && $other_changed[$j]) {
                            $j++;
                        }
                    }
                }
            } while ($runlength != $i - $start);

            /* If possible, move the fully-merged run of changes back to a
             * corresponding run in the other file. */
            while ($corresponding < $i) {
                $changed[--$start] = 1;
                $changed[--$i] = 0;
                assert('$j > 0');
                while ($other_changed[--$j]) {
                    continue;
                }
                assert('$j >= 0 && !$other_changed[$j]');
            }
        }
    }
}

/**
 * The original PHP version of this code was written by Geoffrey T. Dairiki
 * <dairiki@dairiki.org>, and is used/adapted with his permission.
 *
 * Copyright 2004 Geoffrey T. Dairiki <dairiki@dairiki.org>
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-license.php.
 *
 * @package Text_Diff
 * @author  Geoffrey T. Dairiki <dairiki@dairiki.org>
 */
abstract class Horde_Text_Diff_Op_Base
{
    public $orig;
    public $final;

    abstract public function reverse();

    public function norig()
    {
        return $this->orig ? count($this->orig) : 0;
    }

    public function nfinal()
    {
        return $this->final ? count($this->final) : 0;
    }
}

/**
 * The original PHP version of this code was written by Geoffrey T. Dairiki
 * <dairiki@dairiki.org>, and is used/adapted with his permission.
 *
 * Copyright 2004 Geoffrey T. Dairiki <dairiki@dairiki.org>
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-license.php.
 *
 * @package Text_Diff
 * @author  Geoffrey T. Dairiki <dairiki@dairiki.org>
 */
class Horde_Text_Diff_Op_Add extends Horde_Text_Diff_Op_Base
{
    public function __construct($lines)
    {
        $this->final = $lines;
        $this->orig = false;
    }

    public function reverse()
    {
        return new Horde_Text_Diff_Op_Delete($this->final);
    }
}
/**
 * The original PHP version of this code was written by Geoffrey T. Dairiki
 * <dairiki@dairiki.org>, and is used/adapted with his permission.
 *
 * Copyright 2004 Geoffrey T. Dairiki <dairiki@dairiki.org>
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-license.php.
 *
 * @package Text_Diff
 * @author  Geoffrey T. Dairiki <dairiki@dairiki.org>
 */
class Horde_Text_Diff_Op_Change extends Horde_Text_Diff_Op_Base
{
    public function __construct($orig, $final)
    {
        $this->orig = $orig;
        $this->final = $final;
    }

    public function reverse()
    {
        return new Horde_Text_Diff_Op_Change($this->final, $this->orig);
    }
}

/**
 * The original PHP version of this code was written by Geoffrey T. Dairiki
 * <dairiki@dairiki.org>, and is used/adapted with his permission.
 *
 * Copyright 2004 Geoffrey T. Dairiki <dairiki@dairiki.org>
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-license.php.
 *
 * @package Text_Diff
 * @author  Geoffrey T. Dairiki <dairiki@dairiki.org>
 */
class Horde_Text_Diff_Op_Copy extends Horde_Text_Diff_Op_Base
{
    public function __construct($orig, $final = false)
    {
        if (!is_array($final)) {
            $final = $orig;
        }
        $this->orig = $orig;
        $this->final = $final;
    }

    public function reverse()
    {
        return new Horde_Text_Diff_Op_Copy($this->final, $this->orig);
    }
}
/**
 * The original PHP version of this code was written by Geoffrey T. Dairiki
 * <dairiki@dairiki.org>, and is used/adapted with his permission.
 *
 * Copyright 2004 Geoffrey T. Dairiki <dairiki@dairiki.org>
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-license.php.
 *
 * @package Text_Diff
 * @author  Geoffrey T. Dairiki <dairiki@dairiki.org>
 */
class Horde_Text_Diff_Op_Delete extends Horde_Text_Diff_Op_Base
{
    public function __construct($lines)
    {
        $this->orig = $lines;
        $this->final = false;
    }

    public function reverse()
    {
        return new Horde_Text_Diff_Op_Add($this->orig);
    }
}

/**
 * A class to render Diffs in different formats.
 *
 * This class renders the diff in classic diff format. It is intended that
 * this class be customized via inheritance, to obtain fancier outputs.
 *
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-license.php.
 *
 * @package Text_Diff
 */
class Horde_Text_Diff_Renderer
{
    /**
     * Number of leading context "lines" to preserve.
     *
     * This should be left at zero for this class, but subclasses may want to
     * set this to other values.
     */
    protected $_leading_context_lines = 0;

    /**
     * Number of trailing context "lines" to preserve.
     *
     * This should be left at zero for this class, but subclasses may want to
     * set this to other values.
     */
    protected $_trailing_context_lines = 0;

    /**
     * Constructor.
     */
    public function __construct($params = array())
    {
        foreach ($params as $param => $value) {
            $v = '_' . $param;
            if (isset($this->$v)) {
                $this->$v = $value;
            }
        }
    }

    /**
     * Get any renderer parameters.
     *
     * @return array  All parameters of this renderer object.
     */
    public function getParams()
    {
        $params = array();
        foreach (get_object_vars($this) as $k => $v) {
            if ($k[0] == '_') {
                $params[substr($k, 1)] = $v;
            }
        }

        return $params;
    }

    /**
     * Renders a diff.
     *
     * @param Horde_Text_Diff $diff  A Horde_Text_Diff object.
     *
     * @return string  The formatted output.
     */
    public function render($diff)
    {
        $xi = $yi = 1;
        $block = false;
        $context = array();

        $nlead = $this->_leading_context_lines;
        $ntrail = $this->_trailing_context_lines;

        $output = $this->_startDiff();

        $diffs = $diff->getDiff();
        foreach ($diffs as $i => $edit) {
            /* If these are unchanged (copied) lines, and we want to keep
             * leading or trailing context lines, extract them from the copy
             * block. */
            if ($edit instanceof Horde_Text_Diff_Op_Copy) {
                /* Do we have any diff blocks yet? */
                if (is_array($block)) {
                    /* How many lines to keep as context from the copy
                     * block. */
                    $keep = $i == count($diffs) - 1 ? $ntrail : $nlead + $ntrail;
                    if (count($edit->orig) <= $keep) {
                        /* We have less lines in the block than we want for
                         * context => keep the whole block. */
                        $block[] = $edit;
                    } else {
                        if ($ntrail) {
                            /* Create a new block with as many lines as we need
                             * for the trailing context. */
                            $context = array_slice($edit->orig, 0, $ntrail);
                            $block[] = new Horde_Text_Diff_Op_Copy($context);
                        }
                        /* @todo */
                        $output .= $this->_block($x0, $ntrail + $xi - $x0,
                                                 $y0, $ntrail + $yi - $y0,
                                                 $block);
                        $block = false;
                    }
                }
                /* Keep the copy block as the context for the next block. */
                $context = $edit->orig;
            } else {
                /* Don't we have any diff blocks yet? */
                if (!is_array($block)) {
                    /* Extract context lines from the preceding copy block. */
                    $context = array_slice($context, count($context) - $nlead);
                    $x0 = $xi - count($context);
                    $y0 = $yi - count($context);
                    $block = array();
                    if ($context) {
                        $block[] = new Horde_Text_Diff_Op_Copy($context);
                    }
                }
                $block[] = $edit;
            }

            if ($edit->orig) {
                $xi += count($edit->orig);
            }
            if ($edit->final) {
                $yi += count($edit->final);
            }
        }

        if (is_array($block)) {
            $output .= $this->_block($x0, $xi - $x0,
                                     $y0, $yi - $y0,
                                     $block);
        }

        return $output . $this->_endDiff();
    }

    protected function _block($xbeg, $xlen, $ybeg, $ylen, &$edits)
    {
        $output = $this->_startBlock($this->_blockHeader($xbeg, $xlen, $ybeg, $ylen));

        foreach ($edits as $edit) {
            switch (get_class($edit)) {
            case 'Horde_Text_Diff_Op_Copy':
                $output .= $this->_context($edit->orig);
                break;

            case 'Horde_Text_Diff_Op_Add':
                $output .= $this->_added($edit->final);
                break;

            case 'Horde_Text_Diff_Op_Delete':
                $output .= $this->_deleted($edit->orig);
                break;

            case 'Horde_Text_Diff_Op_Change':
                $output .= $this->_changed($edit->orig, $edit->final);
                break;
            }
        }

        return $output . $this->_endBlock();
    }

    protected function _startDiff()
    {
        return '';
    }

    protected function _endDiff()
    {
        return '';
    }

    protected function _blockHeader($xbeg, $xlen, $ybeg, $ylen)
    {
        if ($xlen > 1) {
            $xbeg .= ',' . ($xbeg + $xlen - 1);
        }
        if ($ylen > 1) {
            $ybeg .= ',' . ($ybeg + $ylen - 1);
        }

        // this matches the GNU Diff behaviour
        if ($xlen && !$ylen) {
            $ybeg--;
        } elseif (!$xlen) {
            $xbeg--;
        }

        return $xbeg . ($xlen ? ($ylen ? 'c' : 'd') : 'a') . $ybeg;
    }

    protected function _startBlock($header)
    {
        return $header . "\n";
    }

    protected function _endBlock()
    {
        return '';
    }

    protected function _lines($lines, $prefix = ' ')
    {
        return $prefix . implode("\n$prefix", $lines) . "\n";
    }

    protected function _context($lines)
    {
        return $this->_lines($lines, '  ');
    }

    protected function _added($lines)
    {
        return $this->_lines($lines, '> ');
    }

    protected function _deleted($lines)
    {
        return $this->_lines($lines, '< ');
    }

    protected function _changed($orig, $final)
    {
        return $this->_deleted($orig) . "---\n" . $this->_added($final);
    }
}

/**
 * "Unified" diff renderer, outputting in HTML.
 *
 * This class renders the diff in classic "unified diff" format.
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-license.php.
 *
 * @author  Nicholas K. Dionysopoulos
 */
class Horde_Text_Diff_Renderer_Html extends Horde_Text_Diff_Renderer
{
    /**
     * Number of leading context "lines" to preserve.
     */
    protected $_leading_context_lines = 3;

    /**
     * Number of trailing context "lines" to preserve.
     */
    protected $_trailing_context_lines = 3;

    protected function _blockHeader($xbeg, $xlen, $ybeg, $ylen)
    {
        if ($xlen != 1) {
            $xbeg .= ',' . $xlen;
        }
        if ($ylen != 1) {
            $ybeg .= ',' . $ylen;
        }
        return "<div class=\"ars-diff-header\">@@ -$xbeg +$ybeg @@</div>";
    }

    protected function _context($lines)
    {
		$out = '';
		foreach($lines as $line) {
			$out .= "<div class=\"ars-diff-context\"> $line</div>";
		}
		return $out;
    }

    protected function _added($lines)
    {
		$out = '';
		foreach($lines as $line) {
			$out .= "<div class=\"ars-diff-added\">+$line</div>";
		}
		return $out;
    }

    protected function _deleted($lines)
    {
		$out = '';
		foreach($lines as $line) {
			$out .= "<div class=\"ars-diff-deleted\">-$line</div>";
		}
		return $out;
    }

    protected function _changed($orig, $final)
    {
        return $this->_deleted($orig) . $this->_added($final);
    }
}
