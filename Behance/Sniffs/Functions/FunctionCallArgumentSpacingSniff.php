<?php
/**
 * Generic_Sniffs_Functions_FunctionCallArgumentSpacingSniff.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006-2012 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Generic_Sniffs_Functions_FunctionCallArgumentSpacingSniff.
 *
 * Checks that calls to methods and functions are spaced correctly.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006-2012 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version   Release: 1.4.7
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class Behance_Sniffs_Functions_FunctionCallArgumentSpacingSniff implements PHP_CodeSniffer_Sniff {

  /**
   * Returns an array of tokens this test wants to listen for.
   *
   * @return array
   */
  public function register() {

    return [ T_STRING ];

  } // register

  /**
   * Processes this test, when one of its tokens is encountered.
   *
   * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
   * @param int                  $stackPtr  The position of the current token in the
   *                                        stack passed in $tokens.
   *
   * @return void
   */
  public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {

    $tokens = $phpcsFile->getTokens();

    // Skip tokens that are the names of functions or classes
    // within their definitions. For example:
    // function myFunction...
    // "myFunction" is T_STRING but we should skip because it is not a
    // function or method *call*.
    $functionName    = $stackPtr;
    $ignoreTokens    = PHP_CodeSniffer_Tokens::$emptyTokens;
    $ignoreTokens[]  = T_BITWISE_AND;
    $functionKeyword = $phpcsFile->findPrevious($ignoreTokens, ($stackPtr - 1), null, true);
    if ($tokens[$functionKeyword]['code'] === T_FUNCTION || $tokens[$functionKeyword]['code'] === T_CLASS) {
      return;
    }

    // If the next non-whitespace token after the function or method call
    // is not an opening parenthesis then it cant really be a *call*.
    $openBracket = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, ($functionName + 1), null, true);
    if ($tokens[$openBracket]['code'] !== T_OPEN_PARENTHESIS) {
      return;
    }

    // No need to inspect functions with no arguments
    if ($tokens[($openBracket + 1)]['code'] === T_CLOSE_PARENTHESIS) {
      return;
    }

    $closeBracket  = $tokens[$openBracket]['parenthesis_closer'];
    $nextSeparator = $openBracket;

    if ( $tokens[($closeBracket - 1)]['code'] !== T_WHITESPACE ) {
      $error = 'Expected at least 1 space before closing parenthesis';
      $phpcsFile->addError($error, ($closeBracket - 1), 'SpaceBeforeCloseParens');
    } // if SpaceBeforeCloseParens

    if ( $tokens[($openBracket + 1)]['code'] !== T_WHITESPACE ) {
      $error = 'Expected at least 1 space after opening parenthesis';
      $phpcsFile->addError($error, ($openBracket + 1), 'SpaceAfterOpenParens');
    } // if SpaceAfterOpenParens


    while (($nextSeparator = $phpcsFile->findNext(array(T_COMMA, T_VARIABLE, T_CLOSURE), ($nextSeparator + 1), $closeBracket)) !== false) {

      if ($tokens[$nextSeparator]['code'] === T_CLOSURE) {
        $nextSeparator = $tokens[$nextSeparator]['scope_closer'];
        continue;
      }

      // Make sure the comma or variable belongs directly to this function call,
      // and is not inside a nested function call or array.
      $brackets    = $tokens[$nextSeparator]['nested_parenthesis'];
      $lastBracket = array_pop($brackets);
      if ($lastBracket !== $closeBracket) {
        continue;
      }

      if ($tokens[$nextSeparator]['code'] === T_COMMA) {
        if ($tokens[($nextSeparator - 1)]['code'] === T_WHITESPACE) {
          $error = 'Space found before comma in function call';
          $phpcsFile->addError($error, $stackPtr, 'SpaceBeforeComma');
        }

        if ($tokens[($nextSeparator + 1)]['code'] !== T_WHITESPACE) {
          $error = 'No space found after comma in function call';
          $phpcsFile->addError($error, $stackPtr, 'NoSpaceAfterComma');
        }

        else {

          // If there is a newline in the space, then the must be formatting
          // each argument on a newline, which is valid, so ignore it.
          if (strpos($tokens[($nextSeparator + 1)]['content'], $phpcsFile->eolChar) === false) {

            $space = strlen($tokens[($nextSeparator + 1)]['content']);

            if ($space < 1) {
              $error = 'Expected @ least 1 space after comma in function call';
              $data  = array($space);
              $phpcsFile->addError($error, $stackPtr, 'NoSpaceAfterComma', $data);
            }

          } // if no EOL

        } // else

      } // if next is comma

      else {

        // Token is a variable.
        $nextToken = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, ($nextSeparator + 1), $closeBracket, true);
        if ($nextToken !== false) {

          if ($tokens[$nextToken]['code'] === T_EQUAL) {

            if (($tokens[($nextToken - 1)]['code']) !== T_WHITESPACE) {
              $error = 'Expected 1 space before = sign of default value';
              $phpcsFile->addError($error, $stackPtr, 'NoSpaceBeforeEquals');
            }

            if ($tokens[($nextToken + 1)]['code'] !== T_WHITESPACE) {
              $error = 'Expected 1 space after = sign of default value';
              $phpcsFile->addError($error, $stackPtr, 'NoSpaceAfterEquals');
            }

          } // if next token is =

        } // if next token

      } // else

    } // while

  } // process

} // Behance_Sniffs_Functions_FunctionCallArgumentSpacingSniff