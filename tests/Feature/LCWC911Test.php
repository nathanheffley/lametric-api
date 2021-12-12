<?php

namespace Tests\Feature;

use App\Http\Controllers\LCWC911Controller;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Traits\FrameAuthenticationTests;

class LCWC911Test extends TestCase
{
    use FrameAuthenticationTests;

    protected string $path = '/api/lcwc-911';

    protected int $errorIcon = 55555;

    public function setUp(): void
    {
        parent::setUp();

        Config::set('services.lametric.icons.error', $this->errorIcon);
    }

    public function test_fetches_data_from_correct_url()
    {
        Http::fake([
            'webcad.lcwc911.us/*' => Http::response('', 500),
        ]);

        $this->get($this->path, [
            'Authorization' => $this->basicAuth,
        ]);

        // Todo: move URL to Config
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://webcad.lcwc911.us/Pages/Public/LiveIncidentsFeed.aspx';
        });
    }

    public function test_failed_data_fetch_returns_error_frame()
    {
        Http::fake([
            'webcad.lcwc911.us/*' => Http::response(
                '<rss version="2.0"><channel><title>Lancaster County Live Incidents</title><link>https://webcad.lcwc911.us/Pages/Public/LiveIncidentsFeed.aspx</link><description>Active incidents from Lancaster County-Wide Communications.</description><ttl>5</ttl></channel></rss>',
                500
            ),
        ]);

        $response = $this->get($this->path, [
            'Authorization' => $this->basicAuth,
        ]);

        $response->assertStatus(200);
        $response->assertExactJson([
            'frames' => [
                [
                    'text' => 'ERROR',
                    'icon' => $this->errorIcon,
                ],
            ],
        ]);
    }

    public function test_invalid_xml_returns_error_frame()
    {
        Http::fake([
            'webcad.lcwc911.us/*' => Http::response('not XML'),
        ]);

        $response = $this->get($this->path, [
            'Authorization' => $this->basicAuth,
        ]);

        $response->assertStatus(200);
        $response->assertExactJson([
            'frames' => [
                [
                    'text' => 'ERROR',
                    'icon' => $this->errorIcon,
                ],
            ],
        ]);
    }

    public function test_invalid_item_returns_error_frame()
    {
        Http::fake([
            'webcad.lcwc911.us/*' => Http::response('<rss version="2.0"><channel><title>Lancaster County Live Incidents</title><link>https://webcad.lcwc911.us/Pages/Public/LiveIncidentsFeed.aspx</link><description>Active incidents from Lancaster County-Wide Communications.</description><ttl>5</ttl><item><title>MEDICAL EMERGENCY</title><link>http://www.lcwc911.us/lcwc/lcwc/publiccad.asp</link><description>WEST LAMPETER TOWNSHIP;  TEST RD &amp; TEST ST</description><pubDate>Sat, 11 Dec 2021 17:20:56 GMT</pubDate><guid isPermaLink="false">4q3e2068-ed3b-432f-a396-e9b5xu9d2ht6</guid></item></channel></rss>'),
        ]);

        $response = $this->get($this->path, [
            'Authorization' => $this->basicAuth,
        ]);

        $response->assertStatus(200);
        $response->assertExactJson([
            'frames' => [
                [
                    'text' => 'ERROR',
                    'icon' => $this->errorIcon,
                ],
            ],
        ]);
    }

    public function test_empty_frame_gets_returned_when_no_incidents_are_occurring()
    {
        $expectedFrames = [
            'frames' => [
                [
                    'text' => 'NO LCWC LIVE INCIDENTS AT THIS TIME',
                ],
            ],
        ];

        Cache::shouldReceive('has')
            ->once()
            ->with(LCWC911Controller::KEY)
            ->andReturnFalse();

        Cache::shouldReceive('add')
            ->once()
            ->with(LCWC911Controller::KEY, $expectedFrames, 5 * 60);

        Http::fake([
            'webcad.lcwc911.us/*' => Http::response('<rss version="2.0"><channel><title>Lancaster County Live Incidents</title><link>https://webcad.lcwc911.us/Pages/Public/LiveIncidentsFeed.aspx</link><description>Active incidents from Lancaster County-Wide Communications.</description><ttl>5</ttl></channel></rss>'),
        ]);

        $response = $this->get($this->path, [
            'Authorization' => $this->basicAuth,
        ]);

        $response->assertStatus(200);
        $response->assertExactJson($expectedFrames);
    }

    public function test_empty_frame_gets_returned_when_only_medical_transfers_are_occurring()
    {
        $expectedFrames = [
            'frames' => [
                [
                    'text' => 'NO LCWC LIVE INCIDENTS AT THIS TIME',
                ],
            ],
        ];

        Cache::shouldReceive('has')
            ->once()
            ->with(LCWC911Controller::KEY)
            ->andReturnFalse();

        Cache::shouldReceive('add')
            ->once()
            ->with(LCWC911Controller::KEY, $expectedFrames, 5 * 60);

        Http::fake([
            'webcad.lcwc911.us/*' => Http::response('<rss version="2.0"><channel><title>Lancaster County Live Incidents</title><link>https://webcad.lcwc911.us/Pages/Public/LiveIncidentsFeed.aspx</link><description>Active incidents from Lancaster County-Wide Communications.</description><ttl>5</ttl><item><title>ROUTINE TRANSFER-CLASS 1</title><link>http://www.lcwc911.us/lcwc/lcwc/publiccad.asp</link><description>WEST LAMPETER TOWNSHIP;  TEST RD &amp; TEST ST; MEDIC 01-1; </description><pubDate>Sat, 11 Dec 2021 17:20:56 GMT</pubDate><guid isPermaLink="false">4q3e2068-ed3b-432f-a396-e9b5xu9d2ht6</guid></item><item><title>MEDICAL ROUTINE TRANSFER-CLASS 2</title><link>http://www.lcwc911.us/lcwc/lcwc/publiccad.asp</link><description>EAST HEMPFIELD TOWNSHIP;  GOOD DR &amp; SPRING VALLEY RD; MEDIC 06-9; </description><pubDate>Sat, 11 Dec 2021 17:58:32 GMT</pubDate><guid isPermaLink="false">9c7b7017-ed3b-432f-a396-c0c2ac1c3db7</guid></item><item><title>CLASS 3-ROUTINE TRANSFER</title><link>http://www.lcwc911.us/lcwc/lcwc/publiccad.asp</link><description>WEST HEMPFIELD TOWNSHIP;  BAD DR &amp; FALL HILL RD; MEDIC 9-06; </description><pubDate>Sat, 11 Dec 2021 18:35:30 GMT</pubDate><guid isPermaLink="false">5h9f2950-ed3b-432f-a396-u4h6bu5v7su3</guid></item></channel></rss>'),
        ]);

        $response = $this->get($this->path, [
            'Authorization' => $this->basicAuth,
        ]);

        $response->assertStatus(200);
        $response->assertExactJson($expectedFrames);
    }

    public function test_one_frame_per_incident_is_returned()
    {
        $expectedFrames = [
            'frames' => [
                [
                    'text' => 'LCWC LIVE INCIDENTS',
                ],
                [
                    'text' => 'RESCUE-WATER - COLERAIN TOWNSHIP - SPRUCE GROVE RD & LIBERTY LN - 11 VEHICLES',
                ],
                [
                    'text' => 'FIRE ACTIVITY - ELIZABETHTOWN BOROUGH - N MOUNT JOY ST & SNYDER AVE - 3 VEHICLES',
                ],
                [
                    'text' => 'VEHICLE ACCIDENT-NO INJURIES - LANCASTER TOWNSHIP - MILLERSVILLE PIKE / MICHELLE DR - NO VEHICLES',
                ],
                [
                    'text' => 'VEHICLE ACCIDENT-NO INJURIES - LANCASTER TOWNSHIP - MILLERSVILLE PIKE / MICHELLE DR - 1 VEHICLE',
                ],
            ],
        ];

        Cache::shouldReceive('has')
            ->once()
            ->with(LCWC911Controller::KEY)
            ->andReturnFalse();

        Cache::shouldReceive('add')
            ->once()
            ->with(LCWC911Controller::KEY, $expectedFrames, 5 * 60);

        Http::fake([
            'webcad.lcwc911.us/*' => Http::response('<rss version="2.0"><channel><title>Lancaster County Live Incidents</title><link>https://webcad.lcwc911.us/Pages/Public/LiveIncidentsFeed.aspx</link><description>Active incidents from Lancaster County-Wide Communications.</description><ttl>5</ttl><item><title>RESCUE-WATER</title><link>http://www.lcwc911.us/lcwc/lcwc/publiccad.asp</link><description>COLERAIN TOWNSHIP; SPRUCE GROVE RD &amp; LIBERTY LN; RESCUE 57&lt;br&gt; BOAT 47-1&lt;br&gt; SQUAD 47-1&lt;br&gt; DEPUTY 58&lt;br&gt; BOAT 58-3&lt;br&gt; UTV 58&lt;br&gt; SQUAD 58-1&lt;br&gt; DIVE TEAM 58&lt;br&gt; SQUAD 58-2&lt;br&gt; BOAT 89&lt;br&gt; BRUSH 89;</description><pubDate>Sat, 11 Dec 2021 17:24:51 GMT</pubDate><guid isPermaLink="false">a470e696-c74d-4ebd-8fad-b74cd1af75a9</guid></item><item><title>ROUTINE TRANSFER-CLASS 1</title><link>http://www.lcwc911.us/lcwc/lcwc/publiccad.asp</link><description>WEST LAMPETER TOWNSHIP;  TEST RD &amp; TEST ST; MEDIC 01-1; </description><pubDate>Sat, 11 Dec 2021 17:20:56 GMT</pubDate><guid isPermaLink="false">4q3e2068-ed3b-432f-a396-e9b5xu9d2ht6</guid></item><item><title>FIRE ACTIVITY</title><link>http://www.lcwc911.us/lcwc/lcwc/publiccad.asp</link><description>ELIZABETHTOWN BOROUGH; N MOUNT JOY ST &amp; SNYDER AVE; TRAFFIC 59&lt;br&gt; TRAFFIC 74&lt;br&gt; FIRE POLICE 71;</description><pubDate>Sat, 11 Dec 2021 16:57:43 GMT</pubDate><guid isPermaLink="false">f0dc8501-b72d-4312-8288-b72175afdaf3</guid></item><item><title>VEHICLE ACCIDENT-NO INJURIES</title><link>http://www.lcwc911.us/lcwc/lcwc/publiccad.asp</link><description>LANCASTER TOWNSHIP; MILLERSVILLE PIKE / MICHELLE DR;</description><pubDate>Sat, 11 Dec 2021 21:01:58 GMT</pubDate><guid isPermaLink="false">6af6bf10-b98d-4d2d-880e-f59f0a7aaed3</guid></item><item><title>VEHICLE ACCIDENT-NO INJURIES</title><link>http://www.lcwc911.us/lcwc/lcwc/publiccad.asp</link><description>LANCASTER TOWNSHIP; MILLERSVILLE PIKE / MICHELLE DR; TRAFFIC 99;</description><pubDate>Sat, 11 Dec 2021 21:01:58 GMT</pubDate><guid isPermaLink="false">8df6bf10-b98d-4d2d-880e-f59f0a7yydt6</guid></item></channel></rss>'),
        ]);

        $response = $this->get($this->path, [
            'Authorization' => $this->basicAuth,
        ]);

        $response->assertStatus(200);
        $response->assertExactJson($expectedFrames);
    }

    public function test_cached_frames_are_returned()
    {
        Cache::shouldReceive('has')
            ->once()
            ->with(LCWC911Controller::KEY)
            ->andReturnTrue();

        Cache::shouldReceive('get')
            ->once()
            ->with(LCWC911Controller::KEY)
            ->andReturn([
                'frames' => [
                    [
                        'text' => 'CACHED FRAME',
                    ],
                ],
            ]);

        Http::shouldReceive('get')->never();

        $response = $this->get($this->path, [
            'Authorization' => $this->basicAuth,
        ]);

        $response->assertStatus(200);
        $response->assertExactJson([
            'frames' => [
                [
                    'text' => 'CACHED FRAME',
                ],
            ],
        ]);
    }
}
