<?php

namespace Modules\Listings\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use League\CommonMark\Extension\CommonMark\Parser\Inline\HtmlInlineParser;
use Modules\Properties\Entities\Properties;
use Modules\Properties\Entities\Property;
use PHPHtmlParser\Dom;

class RealStateApiController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('listings::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */

    public function store(Request $request)
    {
        // return "hello";

        try {
            $agentID = "EBFZPR";
            $uniqueID = "Abdul karim";
            $subNumber = 1;
            $streetNumber = 35;
            $street = "st 24 road";
            $suburb = "Wycliffe Well";
            $state = "NT";
            $postcode = "0862";
            $country = "Australia";
            $body = <<<XML
            <propertyList date="2019-06-20-12:30:00">
                        <residential modTime="2019-07-09-12:30:00" status="current">
                            <agentID>$agentID</agentID>
                            <uniqueID>$uniqueID</uniqueID>
                            <address display="no">
                                <subNumber><?php echo $subNumber ?></subNumber>
                                <streetNumber>$streetNumber</streetNumber>
                                <street>$street</street>
                                <suburb display="yes">Wycliffe Well</suburb>
                                <state>NT</state>
                                <postcode>0862</postcode>
                                <country>Australia</country>
                                <display>no</display>
                                <streetview>no</streetview>
                            </address>
                            <authority value="exclusive" />
                            <setSale date="2023-08-01-12:30:00"/>
                            <newConstruction>true</newConstruction>
                            <underOffer value="Yes" />
                            <videoLink href=""/>
                            <price display="yes">400000</price>
                            <priceView>Price on request</priceView>
                            <auction date="2019-07-01T10:30" />
                            <landDetails>
                                <area unit="squareMeter"></area>
                            </landDetails>
                            <buildingDetails>
                                <area unit="square"></area>
                                <energyRating></energyRating>
                            </buildingDetails>
                            <listingAgent id="1">
                                <agentID>EBFZPR</agentID>
                                <name>Agent1 Name Test 2</name>
                                <telephone type="BH">0491 570 006</telephone>
                                <email>agent21@somedomain.com.au</email>
                                <twitterURL/>
                                <facebookURL/>
                                <linkedInURL/>
                            </listingAgent>
                            <listingAgent id="2">
                            </listingAgent>
                            <municipality />
                            <category name="ServicedApartment"/>
                            <headline>Test Listing</headline>
                            <description>
                                REA XML Test Listing by karim
                            </description>
                            <features>
                                <bedrooms>Studio</bedrooms>
                                <bathrooms>1</bathrooms>
                                <toilets>false</toilets>
                                <ensuite>false</ensuite>
                                <garages>false</garages>
                                <openSpaces>false</openSpaces>
                                <carports>false</carports>
                                <remoteGarage>false</remoteGarage>
                                <secureParking>false</secureParking>
                                <airConditioning>false</airConditioning>
                                <broadband>false</broadband>
                                <alarmSystem>false</alarmSystem>
                                <vacuumSystem>false</vacuumSystem>
                                <intercom>false</intercom>
                                <poolInGround>false</poolInGround>
                                <poolAboveGround>false</poolAboveGround>
                                <tennisCourt>false</tennisCourt>
                                <balcony>false</balcony>
                                <deck>false</deck>
                                <courtyard>false</courtyard>
                                <outdoorEnt>false</outdoorEnt>
                                <shed>false</shed>
                                <fullyFenced>false</fullyFenced>
                                <furnished>false</furnished>
                                <openFirePlace>false</openFirePlace>
                                <heating>false</heating>
                                <hotWaterService>false</hotWaterService>
                                <dishwasher>false</dishwasher>
                                <ecoFriendly>
                                    <solarPanels>false</solarPanels>
                                    <solarHotWater>false</solarHotWater>
                                    <waterTank>false</waterTank>
                                    <greyWaterSystem>false</greyWaterSystem>
                                </ecoFriendly>
                                <ductedCooling>false</ductedCooling>
                                <ductedHeating>false</ductedHeating>
                                <builtInRobes>false</builtInRobes>
                                <evaporativeCooling>false</evaporativeCooling>
                                <floorboards>false</floorboards>
                                <gasHeating>false</gasHeating>
                                <hydronicHeating>false</hydronicHeating>
                                <insideSpa>false</insideSpa>
                                <gym>false</gym>
                                <livingAreas>false</livingAreas>
                                <outsideSpa>false</outsideSpa>
                                <payTV>false</payTV>
                                <reverseCycleAirCon>false</reverseCycleAirCon>
                                <rumpusRoom>false</rumpusRoom>
                                <splitSystemAirCon>false</splitSystemAirCon>
                                <splitSystemHeating>false</splitSystemHeating>
                                <study>false</study>
                                <workshop>false</workshop>
                                <otherFeatures></otherFeatures>
                            </features>
                            <inspectionTimes/>
                            <externalLink href=""/>
                            <images>
                                <img id="m" modTime="2009-07-09-14:30:00" url="https://rea-xml-test-images.s3-ap-southeast-2.amazonaws.com/43.jpg" format="jpg"/>
                                <img id="b" />
                                <img id="c"/>
                                <img id="d" />
                                <img id="e" />
                            </images>
                            <objects>
                                <img id="f"  />
                                <img id="g"  />
                                <img id="h" />
                                <img id="i"  />
                                <img id="j"  />
                                <floorplan id="2" />
                                <document id="1" />
                            </objects>
                            <media>
                            </media>
                        </residential>
                    </propertyList>
            XML;

            // return json_encode($body);
            // return response()->json($body);
            // return $body;
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.realestate.com.au/listing/v1/upload",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30000,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => array(
                    // Set Here Your Requesred Headers
                    'Content-Type: text/xml',
                    'Authorization: Bearer 667cb739-14d2-413b-abf7-83823e760a93'
                ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if ($err) {
                // echo "cURL Error #:" . $err;
                return $err;
            } else {
                // print_r(json_decode($response));
                return $response;
            }


            return response()->json([

                'message' => 'Successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }
    public function storetestdeleteit(Request $request)
    {
        // return "hello";

        try {
            $agentID = "EBFZPR";
            $uniqueID = "myday1";
            $subNumber = 1;
            $streetNumber = 35;
            $street = "st 24 road";
            $body = <<<XML
            <propertyList date="2019-06-20-12:30:00">
                        <residential modTime="2019-07-09-12:30:00" status="current">
                            <agentID>{{$agentID}}</agentID>
                            <uniqueID>{{$uniqueID}}</uniqueID>
                            <address display="no">
                                <subNumber>{{}}</subNumber>
                                <streetNumber>35</streetNumber>
                                <street>Test St</street>
                                <suburb display="yes">Wycliffe Well</suburb>
                                <state>NT</state>
                                <postcode>0862</postcode>
                                <country>Australia</country>
                                <display>no</display>
                                <streetview>no</streetview>
                            </address>
                            <authority value="exclusive" />
                            <setSale date="2019-08-01-12:30:00"/>
                            <newConstruction>true</newConstruction>
                            <underOffer value="Yes" />
                            <videoLink href=""/>
                            <price display="yes">400000</price>
                            <priceView>Price on request</priceView>
                            <auction date="2019-07-01T10:30" />
                            <landDetails>
                                <area unit="squareMeter"></area>
                            </landDetails>
                            <buildingDetails>
                                <area unit="square"></area>
                                <energyRating></energyRating>
                            </buildingDetails>
                            <listingAgent id="1">
                                <agentID>EBFZPR</agentID>
                                <name>Agent1 Name Test 2</name>
                                <telephone type="BH">0491 570 006</telephone>
                                <email>agent21@somedomain.com.au</email>
                                <twitterURL/>
                                <facebookURL/>
                                <linkedInURL/>
                            </listingAgent>
                            <listingAgent id="2">
                            </listingAgent>
                            <municipality />
                            <category name="ServicedApartment"/>
                            <headline>Test Listing</headline>
                            <description>
                                REA XML Test Listing by karim
                            </description>
                            <features>
                                <bedrooms>Studio</bedrooms>
                                <bathrooms>1</bathrooms>
                                <toilets>false</toilets>
                                <ensuite>false</ensuite>
                                <garages>false</garages>
                                <openSpaces>false</openSpaces>
                                <carports>false</carports>
                                <remoteGarage>false</remoteGarage>
                                <secureParking>false</secureParking>
                                <airConditioning>false</airConditioning>
                                <broadband>false</broadband>
                                <alarmSystem>false</alarmSystem>
                                <vacuumSystem>false</vacuumSystem>
                                <intercom>false</intercom>
                                <poolInGround>false</poolInGround>
                                <poolAboveGround>false</poolAboveGround>
                                <tennisCourt>false</tennisCourt>
                                <balcony>false</balcony>
                                <deck>false</deck>
                                <courtyard>false</courtyard>
                                <outdoorEnt>false</outdoorEnt>
                                <shed>false</shed>
                                <fullyFenced>false</fullyFenced>
                                <furnished>false</furnished>
                                <openFirePlace>false</openFirePlace>
                                <heating>false</heating>
                                <hotWaterService>false</hotWaterService>
                                <dishwasher>false</dishwasher>
                                <ecoFriendly>
                                    <solarPanels>false</solarPanels>
                                    <solarHotWater>false</solarHotWater>
                                    <waterTank>false</waterTank>
                                    <greyWaterSystem>false</greyWaterSystem>
                                </ecoFriendly>
                                <ductedCooling>false</ductedCooling>
                                <ductedHeating>false</ductedHeating>
                                <builtInRobes>false</builtInRobes>
                                <evaporativeCooling>false</evaporativeCooling>
                                <floorboards>false</floorboards>
                                <gasHeating>false</gasHeating>
                                <hydronicHeating>false</hydronicHeating>
                                <insideSpa>false</insideSpa>
                                <gym>false</gym>
                                <livingAreas>false</livingAreas>
                                <outsideSpa>false</outsideSpa>
                                <payTV>false</payTV>
                                <reverseCycleAirCon>false</reverseCycleAirCon>
                                <rumpusRoom>false</rumpusRoom>
                                <splitSystemAirCon>false</splitSystemAirCon>
                                <splitSystemHeating>false</splitSystemHeating>
                                <study>false</study>
                                <workshop>false</workshop>
                                <otherFeatures></otherFeatures>
                            </features>
                            <inspectionTimes/>
                            <externalLink href=""/>
                            <images>
                                <img id="m" modTime="2009-07-09-14:30:00" url="https://rea-xml-test-images.s3-ap-southeast-2.amazonaws.com/43.jpg" format="jpg"/>
                                <img id="b" />
                                <img id="c"/>
                                <img id="d" />
                                <img id="e" />
                            </images>
                            <objects>
                                <img id="f"  />
                                <img id="g"  />
                                <img id="h" />
                                <img id="i"  />
                                <img id="j"  />
                                <floorplan id="2" />
                                <document id="1" />
                            </objects>
                            <media>
                            </media>
                        </residential>
                    </propertyList>
            XML;

            // return json_encode($body);
            // return response()->json($body);
            // return $body;
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.realestate.com.au/listing/v1/upload",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30000,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => array(
                    // Set Here Your Requesred Headers
                    'Content-Type: text/xml',
                    'Authorization: Bearer e3e11d10-8540-41f6-b1c7-6fceab834ffd'
                ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if ($err) {
                // echo "cURL Error #:" . $err;
                return $err;
            } else {
                // print_r(json_decode($response));
                return $response;
            }


            return response()->json([

                'message' => 'Successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id, $request)
    {
        try {
            $property = Properties::where('id', $request->id)->with('property_address')->first();

            // return $property;
            $id = 1;
            $date = "2022-1-26";
            $current = "4:00 PM";
            $agentID = 'EBFZPR';
            $agentEmail = "agent@gmail.com";
            $uniqueID = 3333;
            $price = 20000;
            $number = 0423;
            $ServicedApartment = 2;


            // return $agentID;
            // return "hello";

            // $subNumber = $property->property_address->number;

            // $streetNumber = $property->property_address->street;
            // $suburb = $property->property_address->suburb;
            // $state = $property->property_address->state;
            // $postcode = $property->property_address->postcode;
            // $country = $property->property_address->country;


            $address =


                "<propertyList date=" . $date .  ">" .
                "<residential modTime=" . $date . "status=" . $current . ">" .
                "<agentID>" . $agentID . "</agentID>" .
                "<uniqueID>" . $uniqueID . "</uniqueID>" .
                "<address display=no>" .
                "<subNumber>" . '$subNumber' . "</subNumber>" .
                "<streetNumber>" . '$streetNumber' . "</streetNumber>" .
                "<street>" . '$streetNumber' . "</street>" .
                "<suburb display=yes>" . '$suburb' . "</suburb>" .
                "<state>" . '$state' . "</state>" .
                "<postcode>" . '$postcode' . "</postcode>" .
                "<country>" . '$country' . "</country>" .
                "<display>no</display>" .
                "<streetview>  no </streetview>" .
                "</address>" .
                "<authority value=exclusive />" .
                "<setSale date=2019-08-01-12:30:00/>" .
                "<newConstruction>true</newConstruction>" .
                "<underOffer value=Yes />" .
                "<videoLink href=/>" .
                "<price display=yes>" . $price . "</price>" .
                "<priceView>" . "Price on request" . "</priceView>" .
                "<auction date=2019-07-01T10:30 />" .
                "<landDetails>" .
                "<area unit=" . "squareMeter" . "></area>" .
                "</landDetails>" .
                "<buildingDetails>" .
                "<area unit=square></area>" .
                "<energyRating></energyRating>" .
                "</buildingDetails>" .
                "<listingAgent id=.$id.>" .
                "<agentID>" . $agentID . "</agentID>" .
                "<name>" . "Agent1 Name Test" . "</name>" .
                "<telephone type=BH>" . $number . "</telephone>" .
                "<email>" . $agentEmail . "</email>" .
                "<twitterURL/>" .
                "<facebookURL/>" .
                "<linkedInURL/>" .
                "</listingAgent>" .

                " <listingAgent id=" . $id . ">" .
                " </listingAgent>" .
                "<municipality />" .
                "<category name=" . $ServicedApartment . "/>" .
                "<headline>Test Listing</headline>" .
                "<description>" .
                "REA XML Test Listing" .
                "</description>" .
                "<features>" .
                "<bedrooms>" . "Studio" . "</bedrooms>" .
                "<bathrooms>" . "bathrooms" . "</bathrooms>" .
                "<toilets>" . "false" . "</toilets>" .
                "<ensuite>" . "false" . "</ensuite>" .
                "<garages>" . "false" . "</garages>" .
                "<openSpaces>" . "false" . "</openSpaces>" .
                "<carports>" . "false" . "</carports>" .
                "<remoteGarage>" . "false" . "</remoteGarage>" .
                "<secureParking>" . "false" . "</secureParking>" .
                "<airConditioning>" . "false" . "</airConditioning>" .
                "<broadband>" . "false" . "</broadband>" .
                "<alarmSystem>" . "false" . "</alarmSystem>" .
                "<vacuumSystem>" . "false" . "</vacuumSystem>" .
                "<intercom>" . "false" . "</intercom>" .
                "<poolInGround>" . "false" . "</poolInGround>" .
                "<poolAboveGround>" . "false" . "</poolAboveGround>" .
                "<tennisCourt>" . "false" . "</tennisCourt>" .
                "<balcony>" . "false" . "</balcony>" .
                "<deck>" . "false" . "</deck>" .
                "<courtyard>" . "false" . "</courtyard>" .
                "<outdoorEnt>" . "false" . "</outdoorEnt>" .
                "<shed>" . "false" . "</shed>" .
                "<fullyFenced>" . "false" . "</fullyFenced>" .
                "<furnished>" . "false" . "</furnished>" .
                "<openFirePlace>" . "false" . "</openFirePlace>" .
                "<heating>" . "false" . "</heating>" .
                "<hotWaterService>" . "false" . "</hotWaterService>" .
                "<dishwasher>" . "false" . "</dishwasher>" .
                "<ecoFriendly>" .
                "<solarPanels>" . false . "</solarPanels>" .
                "<solarHotWater>" . false . "</solarHotWater>" .
                "<waterTank>" . false . "</waterTank>" .
                "<greyWaterSystem>" . false . "</greyWaterSystem>" .
                "</ecoFriendly>" .
                "<ductedCooling>" . false . "</ductedCooling>" .
                "<ductedHeating>" . false . "</ductedHeating>" .
                "<builtInRobes>" . false . "</builtInRobes>" .
                "<evaporativeCooling>" . false . "</evaporativeCooling>" .
                "<floorboards>" . false . "</floorboards>" .
                "<gasHeating>" . false . "</gasHeating>" .
                "<hydronicHeating>" . false . "</hydronicHeating>" .
                "<insideSpa>" . false . "</insideSpa>" .
                "<gym>" . false . "</gym>" .
                "<livingAreas>" . false . "</livingAreas>" .
                "<outsideSpa>" . false . "</outsideSpa>" .
                "<payTV>" . false . "</payTV>" .
                "<reverseCycleAirCon>" . false . "</reverseCycleAirCon>" .
                "<rumpusRoom>" . false . "</rumpusRoom>" .
                "<splitSystemAirCon>" . false . "</splitSystemAirCon>" .
                "<splitSystemHeating>" . false . "</splitSystemHeating>" .
                "<study>false</study>" .
                " <workshop>false</workshop>" .
                "<otherFeatures></otherFeatures>" .
                "</features>" .

                " </residential>" .
                "</propertyList>";

            return $address;
            // return response()->xml($address, 200);
            // return response()->json([
            //     'data' => $address
            // ]);


            // return $reference;
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        ` <propertyList date="2019-06-20-12:30:00">
    <residential modTime="2019-07-09-12:30:00" status="current">
        <agentID>EBFZPR</agentID>
        <uniqueID>karim12345</uniqueID>
        <address display="no">
            <subNumber>1</subNumber>
            <streetNumber>35</streetNumber>
            <street>Test St</street>
            <suburb display="yes">Wycliffe Well</suburb>
            <state>NT</state>
            <postcode>0862</postcode>
            <country>Australia</country>
            <display>no</display>
            <streetview>no</streetview>
        </address>
        <authority value="exclusive" />
        <setSale date="2019-08-01-12:30:00"/>
        <newConstruction>true</newConstruction>
        <underOffer value="Yes" />
        <videoLink href=""/>
        <price display="yes">400000</price>
        <priceView>Price on request</priceView>
        <auction date="2019-07-01T10:30" />
        <landDetails>
            <area unit="squareMeter"></area>
        </landDetails>
        <buildingDetails>
            <area unit="square"></area>
            <energyRating></energyRating>
        </buildingDetails>
        <listingAgent id="1">
            <agentID>EBFZPR</agentID>
            <name>Agent1 Name Test</name>
            <telephone type="BH">0491 570 006</telephone>
            <email>agent1@somedomain.com.au</email>
            <twitterURL/>
            <facebookURL/>
            <linkedInURL/>
        </listingAgent>
        <listingAgent id="2">
        </listingAgent>
        <municipality />
        <category name="ServicedApartment"/>
        <headline>Test Listing</headline>
        <description>
            REA XML Test Listing
        </description>
        <features>
            <bedrooms>Studio</bedrooms>
            <bathrooms>1</bathrooms>
            <toilets>false</toilets>
            <ensuite>false</ensuite>
            <garages>false</garages>
            <openSpaces>false</openSpaces>
            <carports>false</carports>
            <remoteGarage>false</remoteGarage>
            <secureParking>false</secureParking>
            <airConditioning>false</airConditioning>
            <broadband>false</broadband>
            <alarmSystem>false</alarmSystem>
            <vacuumSystem>false</vacuumSystem>
            <intercom>false</intercom>
            <poolInGround>false</poolInGround>
            <poolAboveGround>false</poolAboveGround>
            <tennisCourt>false</tennisCourt>
            <balcony>false</balcony>
            <deck>false</deck>
            <courtyard>false</courtyard>
            <outdoorEnt>false</outdoorEnt>
            <shed>false</shed>
            <fullyFenced>false</fullyFenced>
            <furnished>false</furnished>
            <openFirePlace>false</openFirePlace>
            <heating>false</heating>
            <hotWaterService>false</hotWaterService>
            <dishwasher>false</dishwasher>
            <ecoFriendly>
                <solarPanels>false</solarPanels>
                <solarHotWater>false</solarHotWater>
                <waterTank>false</waterTank>
                <greyWaterSystem>false</greyWaterSystem>
            </ecoFriendly>
            <ductedCooling>false</ductedCooling>
            <ductedHeating>false</ductedHeating>
            <builtInRobes>false</builtInRobes>
            <evaporativeCooling>false</evaporativeCooling>
            <floorboards>false</floorboards>
            <gasHeating>false</gasHeating>
            <hydronicHeating>false</hydronicHeating>
            <insideSpa>false</insideSpa>
            <gym>false</gym>
            <livingAreas>false</livingAreas>
            <outsideSpa>false</outsideSpa>
            <payTV>false</payTV>
            <reverseCycleAirCon>false</reverseCycleAirCon>
            <rumpusRoom>false</rumpusRoom>
            <splitSystemAirCon>false</splitSystemAirCon>
            <splitSystemHeating>false</splitSystemHeating>
            <study>false</study>
            <workshop>false</workshop>
            <otherFeatures></otherFeatures>
        </features>
        <inspectionTimes/>
        <externalLink href=""/>
        <images>
            <img id="m" modTime="2009-07-09-14:30:00" url="https://rea-xml-test-images.s3-ap-southeast-2.amazonaws.com/43.jpg" format="jpg"/>
            <img id="b" />
            <img id="c"/>
            <img id="d" />
            <img id="e" />
        </images>
        <objects>
            <img id="f"  />
            <img id="g"  />
            <img id="h" />
            <img id="i"  />
            <img id="j"  />
            <floorplan id="2" />
            <document id="1" />
        </objects>
        <media>
        </media>
    </residential>
</propertyList>`;
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
