<?php
namespace AkrabatTest;

use Akrabat\OpenWhisk;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class OpenWhiskTest extends TestCase
{
    public function setUp()
    {
        $this->historyContainer = [];

        $_ENV["__OW_API_HOST"] = "http://192.168.33.13:10001";
        $_ENV["__OW_API_KEY"] = "user:password";
    }

    protected function getMockClient(Response $response)
    {
        $history = Middleware::history($this->historyContainer);
        $stack = MockHandler::createWithMiddleware([$response]);
        $stack->push($history);

        return new Client(['handler' => $stack]);
    }


    /**
     * Provider for testTrigger
     *
     * For these trigger, parameter & blocking settings, ensure we POST the correct URL
     * with the correct headers
     *
     * @return  array
     */
    public function triggers()
    {
        return [
            [
                '/guest/demo/hi', // /guest/envphp
                ['place' => 'Paris'],
                'http://192.168.33.13:10001/api/v1/namespaces/guest/triggers/demo/hi?blocking=true'
            ],
            [
                '/guest/hello',
                [],
                'http://192.168.33.13:10001/api/v1/namespaces/guest/triggers/hello?blocking=true'
            ],
        ];
    }

    /**
     * @dataProvider triggers
     */
    public function testTrigger(string $action, array $parameters, string $expectedUri)
    {
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(["response" => ['success' => true]])
        );
        $client = $this->getMockClient($response);

        $whisk = new OpenWhisk($client);

        $result = $whisk->trigger($action, $parameters);

        // check the request sent is correct
        $sentRequest = $this->historyContainer[0]['request'];
        static::assertEquals('POST', $sentRequest->getMethod());
        
        static::assertEquals($expectedUri, (string)$sentRequest->getUri());
        
        $authHeader = 'Basic ' . base64_encode($_ENV["__OW_API_KEY"]);
        static::assertEquals($authHeader, $sentRequest->getHeaderLine('Authorization'));
        static::assertEquals('application/json', $sentRequest->getHeaderLine('Accept'));
        static::assertEquals('application/json', $sentRequest->getHeaderLine('Content-Type'));

        static::assertEquals(json_encode($parameters), (string)$sentRequest->getBody());
    }

    /**
     * Provider for testInvoke
     *
     * For these action, parameter & blocking settings, ensure we POST the correct URL
     * with the correct headers
     *
     * @return  array
     */
    public function invocations()
    {
        return [
            [
                '/guest/demo/hi', // /guest/envphp
                ['place' => 'Paris'],
                true,
                'http://192.168.33.13:10001/api/v1/namespaces/guest/actions/demo/hi?blocking=true'
            ],
            [
                '/guest/hello',
                [],
                true,
                'http://192.168.33.13:10001/api/v1/namespaces/guest/actions/hello?blocking=true'
            ],
            [
                '/guest/hello',
                [],
                false,
                'http://192.168.33.13:10001/api/v1/namespaces/guest/actions/hello?blocking=false'
            ],
        ];
    }

    /**
     * @dataProvider invocations
     */
    public function testInvoke(string $action, array $parameters, bool $blocking, string $expectedUri)
    {
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(["response" => ['success' => true]])
        );
        $client = $this->getMockClient($response);

        $whisk = new OpenWhisk($client);

        $result = $whisk->invoke($action, $parameters, $blocking);

        // check the request sent is correct
        $sentRequest = $this->historyContainer[0]['request'];
        static::assertEquals('POST', $sentRequest->getMethod());
        
        static::assertEquals($expectedUri, (string)$sentRequest->getUri());
        
        $authHeader = 'Basic ' . base64_encode($_ENV["__OW_API_KEY"]);
        static::assertEquals($authHeader, $sentRequest->getHeaderLine('Authorization'));
        static::assertEquals('application/json', $sentRequest->getHeaderLine('Accept'));
        static::assertEquals('application/json', $sentRequest->getHeaderLine('Content-Type'));

        static::assertEquals(json_encode($parameters), (string)$sentRequest->getBody());
    }

    /**
     * Provider for testGetCommunicationsDetails
     *
     * For these __OW_API_HOST & __OW_API_KEY variables, extract the scheme, host, port and auth key
     *
     * @return  array
     */
    public function communicationsDetails()
    {
        return [
            [
                [
                    '__OW_API_HOST' => 'https://192.168.33.13:10001',
                    '__OW_API_KEY' => '1234567890',
                ],
                [ 'https://192.168.33.13:10001', base64_encode('1234567890')]
            ],
            [
                [
                    '__OW_API_HOST' => 'https://192.168.33.13',
                    '__OW_API_KEY' => '1234567890',
                ],
                [ 'https://192.168.33.13', base64_encode('1234567890')]
            ],
            [
                [
                    '__OW_API_HOST' => 'http://192.168.33.13',
                    '__OW_API_KEY' => '1234567890',
                ],
                [ 'http://192.168.33.13', base64_encode('1234567890')]
            ],
            [
                [
                    '__OW_API_HOST' => 'http://192.168.33.13:8080',
                    '__OW_API_KEY' => '1234567890',
                ],
                [ 'http://192.168.33.13:8080', base64_encode('1234567890')]
            ],
        ];
    }

    /**
     * @dataProvider communicationsDetails
     */
    public function testGetCommunicationsDetails($env, $expected)
    {
        $object = new OpenWhisk();
        $reflector = new \ReflectionObject($object);
        $method = $reflector->getMethod('getCommunicationDetails');
        $method->setAccessible(true);
        
        $result = $method->invoke($object, $env);

        self::assertEquals($expected, $result);
    }

    /**
     * Provider for testCanParseQualifiedNames
     *
     * For this action name, split into namespace and name (including package)
     *
     * @return array
     */
    public function qualifiedNames()
    {
        return [
            ['Foo', ['_', 'Foo']],
            ['/Foo', ['_', 'Foo']],
            ['Bar/Foo', ['Bar', 'Foo']],
            ['Bar/Foo/Baz', ['Bar', 'Foo/Baz']],
        ];
    }

    /**
     * @dataProvider qualifiedNames
     */
    public function testCanParseQualifiedNames($name, $expected)
    {
        $object = new OpenWhisk();
        $reflector = new \ReflectionObject($object);
        $method = $reflector->getMethod('parseQualifiedName');
        $method->setAccessible(true);
        
        $result = $method->invoke($object, $name);

        self::assertEquals($expected, $result);
    }
}
