<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2015, Ivan Enderlin. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the Hoa nor the names of its contributors may be
 *       used to endorse or promote products derived from this software without
 *       specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace Hoa\Devtools\Bin;

use Hoa\Console;

/**
 * Class \Hoa\Devtools\Bin\Requiresnapshot.
 *
 * Check if a library requires a new snapshot or not.
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2015 Ivan Enderlin.
 * @license    New BSD License
 */

class Requiresnapshot extends Console\Dispatcher\Kit {

    /**
     * Options description.
     *
     * @var \Hoa\Devtools\Bin\Requiresnapshot array
     */
    protected $options = [
        ['no-verbose', Console\GetOption::NO_ARGUMENT, 'V'],
        ['snapshot',   Console\GetOption::NO_ARGUMENT, 's'],
        ['days',       Console\GetOption::NO_ARGUMENT, 'd'],
        ['commits',    Console\GetOption::NO_ARGUMENT, 'c'],
        ['help',       Console\GetOption::NO_ARGUMENT, 'h'],
        ['help',       Console\GetOption::NO_ARGUMENT, '?']
    ];



    /**
     * The entry method.
     *
     * @access  public
     * @return  int
     */
    public function main ( ) {

        $library       = null;
        $verbose       = Console::isDirect(STDOUT);
        $printSnapshot = false;
        $printDays     = false;
        $printCommits  = false;

        while(false !== $c = $this->getOption($v)) switch($c) {

            case '__ambiguous':
                $this->resolveOptionAmbiguity($v);
              break;

            case 'V':
                $verbose = false;
              break;

            case 's':
                $printSnapshot = true;
              break;

            case 'd':
                $printDays = true;
              break;

            case 'c':
                $printCommits = true;
              break;

            case 'h':
            case '?':
            default:
                return $this->usage();
              break;
        }

        $this->parser->listInputs($library);

        if(empty($library))
            return $this->usage();

        $library = ucfirst(strtolower($library));
        $path    = resolve('hoa://Library/' . $library);

        if(false === file_exists($path))
            throw new Console\Exception(
                'The %s library does not exist.',
                0, $library);

        $tag = Console\Processus::execute(
            'git --git-dir=' . $path . '/.git ' .
                'describe --abbrev=0 --tags origin/master'
        );

        if(empty($tag))
            throw new Console\Exception('No tag.', 1);

        $timeZone       = new \DateTimeZone('UTC');
        $snapshotDT     = \DateTime::createFromFormat(
            '*.y.m.d',
            $tag,
            $timeZone
        );
        $sixWeeks       = new \DateInterval('P6W');
        $nextSnapshotDT = clone $snapshotDT;
        $nextSnapshotDT->add($sixWeeks);
        $today          = new \DateTime('now', $timeZone);

        $needNewSnapshot  = '+' === $nextSnapshotDT->diff($today)->format('%R');
        $numberOfDays     = 0;
        $numberOfCommits  = 0;
        $output           = 'No snapshot is required.';

        if(true === $needNewSnapshot) {

            $numberOfDays    = (int) $nextSnapshotDT->diff($today)->format('%a');
            $numberOfCommits = (int) Console\Processus::execute(
                'git --git-dir=' . $path . '/.git ' .
                    'rev-list ' . $tag . '..origin/master --count'
            );
            $needNewSnapshot = 0 < $numberOfCommits;

            if(true === $needNewSnapshot)
                $output = 'A snapshot is required, since ' . $numberOfDays .
                          ' day' . (1 < $numberOfDays ? 's' : '') .
                          ' (tag ' . $tag . ', ' . $numberOfCommits .
                          ' commit' . (1 < $numberOfCommits ? 's' : '') .
                          ' to publish)!';
        }

        if(   true === $printSnapshot
           || true === $printDays
           || true === $printCommits) {

            $columns = [];

            if(true === $printSnapshot)
                $columns[] = $tag;

            if(true === $printDays)
                $columns[] = $numberOfDays . ' day' .
                             (1 < $numberOfDays ? 's' : '');

            if(true === $printCommits)
                $columns[] = $numberOfCommits . ' commit' .
                             (1 < $numberOfCommits ? 's' : '');

            echo implode("\t", $columns), "\n";
        }
        elseif(true === $verbose)
            echo $output, "\n";

        return !$needNewSnapshot;
    }

    /**
     * The command usage.
     *
     * @access  public
     * @return  int
     */
    public function usage ( ) {

        echo 'Usage   : devtools:requiresnapshot <options> library', "\n",
             'Options :', "\n",
             $this->makeUsageOptionsList([
                 'V'    => 'No-verbose, i.e. be as quiet as possible, just ' .
                           'print essential informations.',
                 's'    => 'Print the latest snapshot name in a column.',
                 'd'    => 'Print the number of days since the latest ' .
                           'snapshot in a column.',
                 'c'    => 'Print the number of commits since the latest ' .
                           'snapshot in a column.',
                 'help' => 'This help.'
             ]), "\n";

        return;
    }
}

__halt_compiler();
Check if a library requires a new snapshot or not.
