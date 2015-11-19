# The Selenium Test Suite

This suite of tests was written to ensure that the flow a user takes is not broken between releases.
We test for UI elements to ensure they are present but also that any click activities remain functional.

## Installation

There are two required components for running tests, the main one being the Selenium Server which you can 
download at the following URL.  You need to install this per the instructions for your operating system.

http://www.seleniumhq.org/download/

The second required component is the chrome web driver which you will find under /vendor/bin/chromedriver
You will have to add that item to your PATH or copy the binary over to /usr/bin which is in every *nix system
path.

## Running the suite

When you wish to run the test suite you first have to make a decision.  Are you going to run multiple tests or 
a single test.

If you are going to run a single test you can do so by executing PHPUnit directly with the following command.

    vendor/bin/phpunit --bootstrap vendor/autoload.php tests/ChoosenTest.php
    
The above command will run all tests in that single test file (hopefully only a handful) by running it solely
through PHPUnit.

If you wish to execute all tests or multiple tests then I would suggest making use of paratest so that the 
tests can be run in parallel.  When they are run in parallel they are executed quite a bit faster but the 
process of running the tests also uses less memory.

To run the tests in parallel you can execute the following command, you can also use paratest if you are running
multiple tests in a single file (if more than a handful).

    vendor/bin/paratest --phpunit vendor/bin/phpunit --bootstrap vendor/autoload.php --path tests/
    vendor/bin/paratest --phpunit vendor/bin/phpunit --bootstrap vendor/autoload.php --path tests/ChoosenTest.php
     
If you wish to make things even easier when running you could always create an alias in your bash_profile.

If you are going to be doing any debugging in a test such as a var_dump and you need to exit
so that you can see the results you will want to use straight phpunit as using paratest will not work.

The output of both straight PHPUnit and paratest is shown below.

    jcrawford@Josephs-Mac-mini:~/Dropbox/Work/HeyTix Dev/heytix_wordpress/tests/selenium # vendor/bin/phpunit --bootstrap vendor/autoload.php tests/
    PHPUnit 4.7.3 by Sebastian Bergmann and contributors.
    .
    Time: 9.68 seconds, Memory: 6.50Mb
    OK (1 test, 1 assertion)
    
    
    jcrawford@Josephs-Mac-mini:~/Dropbox/Work/HeyTix Dev/heytix_wordpress/tests/selenium # vendor/bin/paratest --phpunit vendor/bin/phpunit --bootstrap vendor/autoload.php --path tests/
    Running phpunit in 5 processes with vendor/bin/phpunit
    .    
    Time: 9.22 seconds, Memory: 4.00Mb
    OK (1 test, 1 assertion)
 

The above example is running only 1 test as that is all we have so far, however you can see how it executes faster and
also uses less memory.

This above examples will execute all tests in the test/selenium/tests/ folder (unless otherwise specified).  That is 
any file which has a class and both the class name and file name end with *Test.  


## Creating a Test

If you want to test user registration and login I would suggest
creating a file such as UserActionsTest.php.  Inside this file you will create a class declaration that extends
upon the PHPUnit_Selenium libraries.  You must also make sure that your method name begins with the word test*.
Some examples are ```testHomepageTitle()```, ```testHompageLoginAreaExists()```, etc.

An example skeleton class is shown below.


    <?php
        require_once 'PHPUnit/Extensions/SeleniumTestCase.php';
        class UserActionTest extends PHPUnit_Extensions_Selenium2TestCase
        {
            protected function setUp()
            {
                $this->setBrowser('chrome');
                $this->setBrowserUrl('http://www.heytix.com/');
            }
    
            public function testLogin()
            {
                // test code here
            }
    
            public function testRegistration()
            {
                // test code here
            }
            
            public function testViewProfile() 
            {
            
            }
        }
     ?>