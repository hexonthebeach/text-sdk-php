<?php
require __DIR__ .'/../vendor/autoload.php';


use CMText\TextClient;
use PHPUnit\Framework\TestCase;

class TextClientTest extends TestCase
{

    /**
     * Result of sending a Message when the gateway is unavailable.
     */
    public function testUnavailableGateway()
    {
        $client = new TextClient('your-api-key', 'unavailablehost');

        $result = $client->SendMessage('body-content', 'CM.com', ['00334455667788']);

        $this->assertEquals(
            \CMText\TextClientStatusCodes::UNKNOWN,
            $result->statusCode
        );
    }


    /**
     * The maximum amount of Message objects in a request should be respected
     */
    public function testMessagesLimit()
    {
        $client = new TextClient('your-api-key');

        $this->expectException(\CMText\Exceptions\MessagesLimitException::class);

        $client->send(
            array_fill(
                0,
                TextClient::MESSAGES_MAXIMUM + 1,
                new \CMText\Message()
            )
        );
    }


    /**
     * The Maximum amount of Recipients in a request should be respected
     */
    public function testRecipientsLimit()
    {
        $client = new TextClient('your-api-key', 'unavailablehost');

        $recipients = array_fill(
            0,
            \CMText\Message::RECIPIENTS_MAXIMUM + 1,
            '00334455667788'
        );

        $response = $client->SendMessage('body-content', 'CM.com', $recipients);
        $this->assertEquals(
            'Maximum amount of Recipients exceeded. (' . \CMText\Message::RECIPIENTS_MAXIMUM . ')',
            $response->statusMessage
        );
    }


    /**
     * Building a RichContent Message should result in correctly formatted json
     */
    public function testRichMessageBuilding()
    {
        $message = new \CMText\Message('Message Text', 'Sender_name', ['Recipient_PhoneNumber']);
        $message
            ->WithChannels([\CMText\Channels::WHATSAPP])
            ->WithHybridAppKey('your-secret-hybrid-app-key')
            ->WithRichMessage(
                new \CMText\RichContent\Messages\MediaMessage(
                    'cm.com',
                    'https://avatars3.githubusercontent.com/u/8234794?s=200&v=4',
                    'image/png'
                )
            );

        $this->assertInstanceOf(
            \CMText\Message::class,
            $message
        );

        $this->assertJson( json_encode($message) );

        $json = $message->jsonSerialize();

        $this->assertObjectHasAttribute(
            'allowedChannels',
            $json
        );

        $this->assertObjectHasAttribute(
            'appKey',
            $json
        );

        $this->assertObjectHasAttribute(
            'richContent',
            $json
        );
    }


    /**
     * Building a RichContent Message should result in correctly formatted json
     */
    public function testRichMessageBuildingWithSuggestions()
    {
        $message = new \CMText\Message('Message Text', 'Sender_name', ['Recipient_PhoneNumber']);
        $message
            ->WithChannels([\CMText\Channels::RCS])
            ->WithSuggestions([
                new \CMText\RichContent\Suggestions\ReplySuggestion('Opt In', 'OK'),
                new \CMText\RichContent\Suggestions\ReplySuggestion('Opt Out', 'STOP'),
            ]);


        $this->assertInstanceOf(
            \CMText\Message::class,
            $message
        );

        $this->assertJson( json_encode($message) );

        $json = $message->jsonSerialize();

        $this->assertObjectHasAttribute(
            'allowedChannels',
            $json
        );

        $this->assertObjectHasAttribute(
            'richContent',
            $json
        );
    }


    /**
     * TextClientResult should return a correct object on a weird response
     */
    public function testTextClientResultForWeirdResponses()
    {
        // no json content and no content at all should act this way
        $body = '{[nojson';
        $result = new \CMText\TextClientResult(418, $body);

        $this->assertEquals(
            \CMText\TextClientStatusCodes::UNKNOWN,
            $result->statusCode
        );
        $this->assertEquals(
            $body,
            $result->statusMessage
        );
    }

}
