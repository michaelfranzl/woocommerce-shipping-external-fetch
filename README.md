# woocommerce-shipping-external-fetch

Shipping Plugin for WooCommerce which HTTP PUTs the cart contents in JSON format to an
external web service specified by protocol/host/port/URI, and receives the
calculated shipping offer (costs, labels etc.) as JSON from that webservice.

Useful when the shipping calculation complexity exceeds the capabilities of other
shipping calculation plugins.

The external shipping calculation application is, of course, business specific and
thus not included in this repository. However, an example of a JSON request and
reponse is shown below.

When the webservice is not reachable, not responsive, or returns HTTP status codes
other than 200 (i.e. when it experiences a server error), this plugin offers
free shipping to the customer, since technical problems are the 'fault' of the
store owner and should not prevent a customer from completing an order. A setting
to configure this behavior is not included, but it would be easy to add.

This plugin supports WooCommerce shipping zones. So, in theory you could have
different web services dedicated to different shipping zones.

To add the plugin to an existing shipping zone:

1. Go to WooCommerce -> Settings -> Shipping
2. Click on "Manage shipping methods" below a Zone
3. Click "Add shipping method" button
4. Select "External Fetch" and click "Add shipping method" button
5. Configure the Plugin by clicking on "Edit"
6. Customize "Method title" and "Method description" and set the "JSON API Endpoint" to, for example `http://localhost:4040/calculate`

At this point you can add a product to the cart, set up a simple webserver listening at port 4040 on the same machine, and receive/send JSON as shown in the section "Examples" further below.


This plugin is for DEVELOPERS only and will likely remain in an ALPHA state.


## License

Copyright 2017 Michael Franzl

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.


# Examples

**Cart contents in JSON format as sent by this plugin**:

    {
      "contents": {
          "158f3069a435b314a80bdcb024f8e422": {
              "product_id": "313",
              "variation_id": "0",
              "quantity": "1",
              "line_total": "10",
              "line_subtotal": "10",
              "line_tax": "0",
              "line_subtotal_tax": "0",
              "shipping_class_slug": "shipping-class-slug1",
              "categories": [
                  "cat-slug-1",
                  "cat-slug-2"
              ],
              "attributes": {
                  "blah": [
                      "content1",
                      "content2"
                  ],
                  "pa_delay": [
                      "10"
                  ]
              },
              "dimensions": {
                  "length": "",
                  "width": "",
                  "height": "14"
              },
              "purchase_note": "purchase note",
              "weight": "300",
              "downloadable": "0",
              "virtual": "0"
          },
          "e0e0fbbe4ccee55c005987fd6c6b75c8": {
              "product_id": "350",
              "variation_id": "353",
              "variation": {
                  "attribute_color": "blue"
              },
              "quantity": "1",
              "line_total": "13",
              "line_subtotal": "13",
              "line_tax": "0",
              "line_subtotal_tax": "0",
              "data": {
                  "post_type": "product_variation"
              },
              "shipping_class_slug": "bulky-items",
              "attributes": [
                  "blue"
              ],
              "dimensions": {
                  "length": "",
                  "width": "",
                  "height": "60"
              },
              "purchase_note": "",
              "weight": "400",
              "downloadable": "0",
              "virtual": "0"
          }
      },
      "contents_cost": "23",
      "user": {
          "ID": "0"
      },
      "destination": {
          "country": "US",
          "state": "",
          "postcode": "",
          "city": "",
          "address": "",
          "address_2": ""
      },
      "site": {
          "locale": "en_US"
      },
      "shipping_method": {
          "instance_id": "20"
      }
    }

    
**Response in JSON format as expected by this plugin**:

This plugin will add the `description` values in a `<div class="shipping_rate_description"` below each selectable shipping rate entry on the checkout page to give the customer a better idea about the shipping method.

You can put a message into `cart_no_shipping_available_html` which will be added to the default WooCommerce message when there are no shipping methods available.


    {
      "rates": [
        { 
          id: "20_1",
          cost: 11.1,
          label: "Standard Postal Service",
          description: "<b>without</b> Tracking"
        },
        { 
          id: "20_2",
          cost: 12.2,
          label: "Prio International",
          description: "<b>with</b> Tracking"
        },
      ],
      "notices": {
        "successes": [],
        "notices": ["This is a notice"],
        "errors": [],
      },
      "before_checkout_button_html: "Shipping prices calculated by plugin woocommerce-shipping-external-fetch!",
      "cart_no_shipping_available_html": ""
    }
