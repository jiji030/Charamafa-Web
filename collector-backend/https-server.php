<?php
// HTTPS Server for Laravel with SSL support
use React\Socket\TcpServer;
use React\Socket\SecureServer;
use React\Http\HttpServer;
use React\Http\Message\Response;

require __DIR__ . '/vendor/autoload.php';

// Start Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create socket server
$loop = \React\EventLoop\Loop::get();

// Configure SSL context
$context = [
    'tls' => [
        'local_cert' => __DIR__ . '/localhost+2.pem',
        'local_pk' => __DIR__ . '/localhost+2-key.pem',
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true,
    ]
];

$socket = new TcpServer('192.168.1.26:8000', $loop);
$socket = new SecureServer($socket, $loop, $context);

$server = new HttpServer($loop, function (\Psr\Http\Message\ServerRequestInterface $request) use ($kernel) {
    try {
        // Convert ReactPHP request to Laravel request
        $laravelRequest = \Illuminate\Http\Request::create(
            $request->getUri(),
            $request->getMethod(),
            [],
            [],
            [],
            [
                'HTTP_HOST' => $request->getHeaderLine('Host'),
                'HTTP_USER_AGENT' => $request->getHeaderLine('User-Agent'),
                'HTTP_ACCEPT' => $request->getHeaderLine('Accept'),
                'HTTP_ACCEPT_LANGUAGE' => $request->getHeaderLine('Accept-Language'),
                'HTTP_ACCEPT_ENCODING' => $request->getHeaderLine('Accept-Encoding'),
                'HTTP_CONNECTION' => $request->getHeaderLine('Connection'),
                'HTTP_AUTHORIZATION' => $request->getHeaderLine('Authorization'),
                'CONTENT_TYPE' => $request->getHeaderLine('Content-Type'),
            ]
        );
        
        if ($request->getMethod() !== 'GET' && $request->getMethod() !== 'HEAD') {
            $laravelRequest->merge(json_decode($request->getBody()->getContents(), true) ?: []);
        }

        // Process through Laravel
        $response = $kernel->handle($laravelRequest);
        $kernel->terminate($laravelRequest, $response);

        // Convert Laravel response to ReactPHP response
        return new Response(
            $response->getStatusCode(),
            array_merge($response->headers->all(), [
                'Access-Control-Allow-Origin' => ['https://192.168.1.26:3000'],
                'Access-Control-Allow-Methods' => ['GET, POST, PUT, DELETE, OPTIONS'],
                'Access-Control-Allow-Headers' => ['Content-Type, Authorization'],
                'Access-Control-Allow-Credentials' => ['true'],
            ]),
            $response->getContent()
        );
    } catch (\Exception $e) {
        return new Response(500, [], json_encode(['error' => $e->getMessage()]));
    }
});

$server->listen($socket);

echo "HTTPS Laravel server running at https://192.168.1.26:8000\n";
echo "SSL Certificates: localhost+2.pem\n";
echo "Press Ctrl+C to stop the server\n";

$loop->run();
