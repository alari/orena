<?php
/**
 * PHPUnit
 *
 * Copyright (c) 2002-2008, Sebastian Bergmann <sb@sebastian-bergmann.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 * 
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   Testing
 * @package    PHPUnit
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2002-2008 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    SVN: $Id: TestSuiteIterator.php,v 1.5.6.1 2008-04-09 16:22:00 nir.c Exp $
 * @link       http://www.phpunit.de/
 * @since      File available since Release 3.1.0
 */

require_once 'PHPUnit/Util/Filter.php';

PHPUnit_Util_Filter::addFileToFilter(__FILE__, 'PHPUNIT');

/**
 * Iterator for test suites.
 *
 * @category   Testing
 * @package    PHPUnit
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2002-2008 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: 3.2.18
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 3.1.0
 */
class PHPUnit_Util_TestSuiteIterator implements RecursiveIterator
{
    /**
     * @var    integer
     * @access protected
     */
    protected $position;

    /**
     * @var    PHPUnit_Framework_Test[]
     * @access protected
     */
    protected $tests;

    /**
     * Constructor.
     *
     * @param  PHPUnit_Framework_TestSuite $suite
     * @access public
     */
    public function __construct(PHPUnit_Framework_TestSuite $testSuite)
    {
        $this->tests = $testSuite->tests();
    }

    /**
     * Rewinds the Iterator to the first element.
     *
     * @access public
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * Checks if there is a current element after calls to rewind() or next().
     *
     * @return boolean
     * @access public
     */
    public function valid()
    {
        return $this->position < count($this->tests);
    }

    /**
     * Returns the key of the current element.
     *
     * @return integer
     * @access public
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Returns the current element.
     *
     * @return PHPUnit_Framework_Test
     * @access public
     */
    public function current()
    {
        return $this->valid() ? $this->tests[$this->position] : NULL;
    }

    /**
     * Moves forward to next element.
     *
     * @access public
     */
    public function next()
    {
        $this->position++;
    }

    /**
     * Returns the sub iterator for the current element.
     *
     * @return PHPUnit_Util_TestSuiteIterator
     * @access public
     */
    public function getChildren()
    {
        return new PHPUnit_Util_TestSuiteIterator($this->tests[$this->position]);
    }

    /**
     * Checks whether the current element has children.
     *
     * @return boolean
     * @access public
     */
    public function hasChildren()
    {
        return $this->tests[$this->position] instanceof PHPUnit_Framework_TestSuite;
    }
}
?>
