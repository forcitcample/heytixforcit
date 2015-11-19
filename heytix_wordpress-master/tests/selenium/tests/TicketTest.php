<?php
require_once 'PHPUnit/Extensions/SeleniumTestCase.php';
class TicketTest extends PHPUnit_Extensions_Selenium2TestCase
{
    protected function setUp()
    {
        $this->setBrowser('chrome');
        $this->setBrowserUrl('http://www.heytix.com/');
    }

    public function testRegisteredUserTicketPurchase() {
        $this->markTestSkipped();
        $this->url('http://staging.heytix.com/event/4b-haven/');
        sleep(2);
        $this->byXPath("(//table[contains(@class, 'tribe-events-tickets')]/tbody/tr)[1]/td[contains(@class, 'woocommerce ht_quantity')]/select[contains(@class, 'htmh_ticket_number')]")->value(1);

        $this->execute(array(
            'script' => "jQuery('.vamtam-button').click();",
            'args' => array()
        ));
        sleep(2);
        // continue the checkout process
        $this->byXPath("//a[contains(@class, 'checkout-button')]")->click();
        sleep(2);
        try {

            try {
                $billing_header = $this->byXPath("//div[@class='woocommerce-billing-fields']/h3")->value();
            } catch(PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {
                if(substr($e->getMessage(), 0, 15) == 'no such element') {
                    $this->fail('It does not appear to be on the billing details page.');
                }
            }

            try {
                // enter values for the form
                $this->byName('billing_first_name')->value('Joseph');
                $this->byName('billing_last_name')->value('Crawford');
                $this->byName('billing_address_1')->value('940 Water St.');
                $this->byName('billing_address_2')->value('Box 19');
                $this->byName('billing_city')->value('North Bennington');


                // deal with state selection
                $script = "jQuery('#billing_state').val('VT').trigger('change');";
                $result = $this->execute(array('script' => $script, 'args'   => array()));

                $this->execute(array(
                    'script' => "jQuery(window).scrollTop(jQuery('#terms').offset().top);",
                    'args' => array()
                ));

                $this->byName('billing_postcode')->value('05257');
                $this->byName('billing_email')->value('info@josephcrawford.com');
                $this->byName('billing_phone')->value('781-346-9656');
                sleep(4);
                $this->byId('terms')->click();
                sleep(2);
                $this->byId('place_order')->click();
                sleep(2);
                try {
                    $this->execute(array(
                        'script' => "jQuery('.stripe_checkout_app').attr('id', 'stripe-frame');",
                        'args' => array()
                    ));

                    sleep(3);

                    $this->frame($this->byId('stripe-frame'));

                    $this->byId('card_number')->value('4242424242424242');
                    $this->byId('cc-exp')->value('11/18');
                    $this->byId('cc-csc')->value('3456');
                    $this->byClassName('iconTick')->click();
                } catch(PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {
                    if(substr($e->getMessage(), 0, 15) == 'no such element') {
                        $this->fail('Unable to find the card elements');
                    }
                }

                sleep(18);
                $element = $this->byXPath("//p[@class='ht-center']");
                $this->assertEquals('Thank you. Your order has been received.', $element->text());

            } catch(PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {
                if(substr($e->getMessage(), 0, 15) == 'no such element') {
                    $this->fail('Unable to enter billing information to proceed.');
                }
            }


        } catch(PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {
            if(substr($e->getMessage(), 0, 15) == 'no such element') {
                $this->fail('It does not appear to be the cart page.');
            }
        }

    }

    /**
     * Test Facebook User Guestlist
     *
     * This test will test the guestlist registration process for a facebook account user.
     *
     */
    public function testFacebookUserGuestlist()
    {
        $this->markTestSkipped();
        $this->url('http://www.facebook.com/');
        sleep(6);
        $this->byName('email')->value('mary_wirsyag_zuckerberg@tfbnw.net');
        $this->byName('pass')->value('tester123');
        $this->byId("loginbutton")->click();
        sleep(4);
        $target_element = 4;
        $this->url('http://staging.heytix.com/event/dada-life-at-haven/');

        // click the event guestlist button
        $this->byClassName('userpro-social-facebook')->click();
        sleep(8);
        $this->byClassName('button')->click();
        sleep(6);
        $message = strtoupper(trim($this->byXPath("//div[contains(@class, 'page-header-content')]/h1/span[contains(@class, 'title')]")->text()));
        $this->assertEquals('PLEASE CHECK YOUR EMAIL', $message);
    }

    /**
     * Test Registered User Guestlist
     *
     * This test will test the guestlist registration process for a wordpress account user.
     *
     */
    public function testRegisteredUserGuestlist()
    {
        $this->url('http://staging.heytix.com/event/dada-life-at-haven/');

        // click login with Heytix account link
        $script = "jQuery('.htgl-signup-box p a').get(0).click();";
        $result = $this->execute(array('script' => $script, 'args'   => array()));

        try {
            $username = $this->byId('gl_username');
            $password = $this->byId('gl_password');
            $username->value('tester');
            $password->value('h3yt1xt35t');

            $this->byXPath('//div[contains(@class, "gl-login-form")]/form/p/button[@type="submit"]')->click();
            sleep(10);
            $message = $this->byXPath('//div[@class="htgl-messages"]/p')->text();
            $this->assertEquals('Thank you for registering. Enjoy the event.', $message);

        } catch(PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {
            if(substr($e->getMessage(), 0, 15) == 'no such element') {
                $this->fail('Unable to locate requested element on the page.');
            }
        }
        $this->assertEquals('Guest List Confirmation » Heytix', $this->title());
    }



}
?>
