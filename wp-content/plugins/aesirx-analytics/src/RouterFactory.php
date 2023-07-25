<?php

namespace AesirxAnalytics;

use AesirxAnalytics\Route\Middleware\IsBackendMiddleware;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Event\EventArgument;
use Pecee\SimpleRouter\Handlers\EventHandler;
use Pecee\SimpleRouter\Route\IGroupRoute;
use Pecee\SimpleRouter\Route\ILoadableRoute;
use Pecee\SimpleRouter\Route\RouteGroup;
use Pecee\SimpleRouter\Route\RouteUrl;
use Pecee\SimpleRouter\Router;

/**
 * RouterFactory
 */
class RouterFactory {
    /**
     * @var callable
     */
    private $callback;
    private Router $router;
    private string $uuidMatch = '[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}';
    private array $requestBody;

    /**
     * @param callable $callback
     */
    public function __construct( callable $callback, string $basePath ) {
        $this->callback    = $callback;
        $this->router      = ( new Router )
            ->setRenderMultipleRoutes( false );
        $this->requestBody = (array) json_decode( file_get_contents( 'php://input' ), true );

        if ( ! empty( $basePath ) ) {
            $this->router->addEventHandler(
                ( new EventHandler )
                    ->register( EventHandler::EVENT_ADD_ROUTE, function ( EventArgument $event ) use ( $basePath ) {
                        // Skip routes added by group as these will inherit the url
                        if ( ! $event->__get( 'isSubRoute' ) ) {
                            return;
                        }

                        $route = $event->__get( 'route' );

                        switch ( true ) {
                            case $route instanceof ILoadableRoute:
                                $route->prependUrl( $basePath );
                                break;
                            case $route instanceof IGroupRoute:
                                $route->prependPrefix( $basePath );
                                break;
                        }
                    } )
            );
        }

        $this->router->addRoute(
            ( new RouteUrl( '/wallet/v1/{network}/{address}/nonce', function ( string $network, string $address ) {
                return call_user_func( $this->callback, array_merge(
                    [ 'wallet', 'v1', 'nonce', '--network', $network, '--address', $address ],
                    $this->apply_if_not_empty( $this->requestBody, [
                        'text' => 'text',
                    ] )
                ) );
            } ) )->setRequestMethods( [ Request::REQUEST_TYPE_POST ] )
        );

        $this->router->addRoute(
            ( new RouteGroup )
                ->setSettings( [ 'prefix' => '/consent/v1' ] )
                ->setCallback(
                    function () {
                        $this->router->addRoute(
                            ( new RouteUrl( '/level1/{uuid}/{consent}', function ( string $uuid, string $consent ) {
                                return call_user_func( $this->callback, [
                                    'consent',
                                    'level1',
                                    'v1',
                                    '--uuid',
                                    $uuid,
                                    '--consent',
                                    $consent
                                ] );
                            } ) )
                                ->setRequestMethods( [ Request::REQUEST_TYPE_POST ] )
                        );

                        $this->router->addRoute(
                            ( new RouteGroup )
                                ->setSettings( [ 'prefix' => '/level2' ] )
                                ->setCallback(
                                    function () {
                                        $this->router->addRoute(
                                            ( new RouteUrl( '/list', function () {
                                                return call_user_func( $this->callback, [
                                                    'list-consent',
                                                    'level2',
                                                    'v1',
                                                    '--token',
                                                    $this->getToken(),
                                                ] );
                                            } ) )
                                                ->setRequestMethods( [ Request::REQUEST_TYPE_GET ] )
                                        );
                                        $this->router->addRoute(
                                            ( new RouteUrl( '/revoke/{consent_uuid}', function ( string $consent_uuid ) {
                                                return call_user_func( $this->callback, [
                                                    'revoke',
                                                    'level2',
                                                    'v1',
                                                    '--consent-uuid',
                                                    $consent_uuid,
                                                    '--token',
                                                    $this->getToken(),
                                                ] );
                                            } ) )
                                                ->setWhere( [ 'consent_uuid' => $this->uuidMatch ] )
                                                ->setRequestMethods( [ Request::REQUEST_TYPE_PUT ] )
                                        );
                                        $this->router->addRoute(
                                            ( new RouteUrl( '/{uuid}', function ( string $uuid ) {
                                                return call_user_func( $this->callback, array_merge( [
                                                    'consent',
                                                    'level2',
                                                    'v1',
                                                    '--uuid',
                                                    $uuid,
                                                    '--token',
                                                    $this->getToken(),
                                                ],
                                                    $this->apply_if_not_empty( $this->requestBody, [
                                                        'consent' => 'consent',
                                                    ] ) ) );
                                            } ) )
                                                ->setWhere( [ 'uuid' => $this->uuidMatch ] )
                                                ->setRequestMethods( [ Request::REQUEST_TYPE_POST ] )
                                        );

                                    } )
                        );

                        $this->router->addRoute(
                            ( new RouteGroup )
                                ->setSettings( [ 'prefix' => '/level3' ] )
                                ->setCallback(
                                    function () {
                                        $this->router->addRoute(
                                            ( new RouteUrl( '/list/{network}/{wallet}', function ( string $network, string $wallet ) {
                                                return call_user_func( $this->callback, array_merge( [
                                                    'list-consent',
                                                    'level3',
                                                    'v1',
                                                    '--network',
                                                    $network,
                                                    '--wallet',
                                                    $wallet,
                                                ], $this->apply_if_not_empty( $this->router->getRequest()->getUrl()->getParams(), [ 'signature' => 'signature' ] )
                                                ) );
                                            } ) )
                                                ->setRequestMethods( [ Request::REQUEST_TYPE_GET ] )
                                        );
                                        $this->router->addRoute(
                                            ( new RouteUrl( '/revoke/{consent_uuid}/{network}/{wallet}', function ( string $consent_uuid, string $network, string $wallet ) {
                                                return call_user_func( $this->callback, array_merge(
                                                    [
                                                        'revoke',
                                                        'level3',
                                                        'v1',
                                                        '--consent-uuid',
                                                        $consent_uuid,
                                                        '--network',
                                                        $network,
                                                        '--wallet',
                                                        $wallet,
                                                    ],
                                                    $this->apply_if_not_empty( $this->requestBody, [
                                                        'signature' => 'signature',
                                                    ] )
                                                ) );
                                            } ) )
                                                ->setWhere( [ 'consent_uuid' => $this->uuidMatch ] )
                                                ->setRequestMethods( [ Request::REQUEST_TYPE_PUT ] )
                                        );
                                        $this->router->addRoute(
                                            ( new RouteUrl( '/{uuid}/{network}/{wallet}', function ( string $uuid, string $network, string $wallet ) {
                                                return call_user_func( $this->callback, array_merge(
                                                    [
                                                        'consent',
                                                        'level3',
                                                        'v1',
                                                        '--visitor-uuid',
                                                        $uuid,
                                                        '--network',
                                                        $network,
                                                        '--wallet',
                                                        $wallet
                                                    ],
                                                    $this->apply_if_not_empty( $this->requestBody, [
                                                        'consent'   => 'consent',
                                                        'signature' => 'signature',
                                                    ] )
                                                ) );
                                            } ) )
                                                ->setWhere( [ 'uuid' => $this->uuidMatch ] )
                                                ->setRequestMethods( [ Request::REQUEST_TYPE_POST ] )
                                        );
                                    } )
                        );

                        $this->router->addRoute(
                            ( new RouteGroup )
                                ->setSettings( [ 'prefix' => '/level4' ] )
                                ->setCallback(
                                    function () {
                                        $this->router->addRoute(
                                            ( new RouteUrl( '/list/{network}/{web3id}/{wallet}', function ( string $network, string $web3id, string $wallet ) {
                                                return call_user_func( $this->callback, array_merge( [
                                                    'list-consent',
                                                    'level4',
                                                    'v1',
                                                    '--network',
                                                    $network,
                                                    '--wallet',
                                                    $wallet,
                                                    '--web3id',
                                                    $web3id,
                                                ], $this->apply_if_not_empty( $this->router->getRequest()->getUrl()->getParams(), [
                                                    'signature' => 'signature',
                                                ] ) ) );
                                            } ) )
                                                ->setWhere( [
                                                    'web3id' => '[\@\w-]+',
                                                ] )
                                                ->setRequestMethods( [ Request::REQUEST_TYPE_GET ] )
                                        );
                                        $this->router->addRoute(
                                            ( new RouteUrl( '/revoke/{consent_uuid}/{network}/{web3id}/{wallet}', function ( string $consent_uuid, string $network, string $web3id, string $wallet ) {
                                                return call_user_func( $this->callback, array_merge(
                                                    [
                                                        'revoke',
                                                        'level4',
                                                        'v1',
                                                        '--consent-uuid',
                                                        $consent_uuid,
                                                        '--network',
                                                        $network,
                                                        '--wallet',
                                                        $wallet,
                                                        '--web3id',
                                                        $web3id,
                                                    ],
                                                    $this->apply_if_not_empty( $this->requestBody, [
                                                        'signature' => 'signature',
                                                    ] )
                                                ) );
                                            } ) )
                                                ->setWhere( [
                                                    'consent_uuid' => $this->uuidMatch,
                                                    'web3id'       => '[\@\w-]+',
                                                ] )
                                                ->setRequestMethods( [ Request::REQUEST_TYPE_PUT ] )
                                        );
                                        $this->router->addRoute(
                                            ( new RouteUrl( '/{uuid}/{network}/{web3id}/{wallet}', function ( string $uuid, string $network, string $web3id, string $wallet ) {
                                                return call_user_func( $this->callback, array_merge(
                                                    [
                                                        'consent',
                                                        'level4',
                                                        'v1',
                                                        '--visitor-uuid',
                                                        $uuid,
                                                        '--network',
                                                        $network,
                                                        '--wallet',
                                                        $wallet,
                                                        '--web3id',
                                                        $web3id,
                                                    ],
                                                    $this->apply_if_not_empty( $this->requestBody, [
                                                        'consent'   => 'consent',
                                                        'signature' => 'signature',
                                                    ] )
                                                ) );
                                            } ) )
                                                ->setWhere( [
                                                    'uuid'   => $this->uuidMatch,
                                                    'web3id' => '[\@\w-]+',
                                                ] )
                                                ->setRequestMethods( [ Request::REQUEST_TYPE_POST ] )
                                        );
                                    } )
                        );
                    }
                )
        );

        $this->router->addRoute(
            ( new RouteGroup )
                ->setSettings( [ 'prefix' => '/visitor' ] )
                ->setCallback(
                    function () {
                        $this->router->addRoute(
                            ( new RouteGroup )
                                ->setSettings( [ 'prefix' => '/v1' ] )
                                ->setCallback(
                                    function () {
                                        $this->router->addRoute(
                                            ( new RouteUrl( '/init', function () {
                                                return call_user_func( $this->callback, array_merge( [
                                                    'visitor',
                                                    'init',
                                                    'v1',
                                                    '--ip',
                                                    empty( $this->requestBody['ip'] ) ? $this->router->getRequest()->getIp() : $this->requestBody['ip'],
                                                ], $this->apply_if_not_empty( $this->requestBody, [
                                                    'user_agent'      => 'user-agent',
                                                    'device'          => 'device',
                                                    'browser_name'    => 'browser-name',
                                                    'browser_version' => 'browser-version',
                                                    'lang'            => 'lang',
                                                    'url'             => 'url',
                                                    'referer'         => 'referer',
                                                    'event_name'      => 'event-name',
                                                    'event_type'      => 'event-type',
                                                ] ), $this->apply_attributes() ) );
                                            } ) )
                                                ->setRequestMethods( [ Request::REQUEST_TYPE_POST ] )
                                        );
                                        $this->router->addRoute(
                                            ( new RouteUrl( '/start', function () {
                                                return call_user_func( $this->callback, array_merge(
                                                    [ 'visitor', 'start', 'v1' ],
                                                    $this->apply_if_not_empty( $this->requestBody, [
                                                        'visitor_uuid' => 'visitor-uuid',
                                                        'url'          => 'url',
                                                        'referer'      => 'referer',
                                                        'event_name'   => 'event-name',
                                                        'event_type'   => 'event-type',
                                                        'event_uuid'   => 'event-uuid',
                                                    ] ),
                                                    $this->apply_attributes()
                                                ) );
                                            } ) )
                                                ->setRequestMethods( [ Request::REQUEST_TYPE_POST ] )
                                        );
                                        $this->router->addRoute(
                                            ( new RouteUrl( '/end', function () {
                                                return call_user_func( $this->callback, array_merge(
                                                    [ 'visitor', 'end', 'v1' ],
                                                    $this->apply_if_not_empty( $this->requestBody, [
                                                        'visitor_uuid' => 'visitor-uuid',
                                                        'event_uuid'   => 'event-uuid',
                                                    ] )
                                                ) );
                                            } ) )
                                                ->setRequestMethods( [ Request::REQUEST_TYPE_POST ] )
                                        );
                                        $this->router->addRoute(
                                            ( new RouteUrl( '/{uuid}', function ( string $uuid ) {
                                                return call_user_func( $this->callback, [
                                                    'get',
                                                    'visitor',
                                                    'v1',
                                                    '--uuid',
                                                    $uuid
                                                ] );
                                            } ) )
                                                ->setWhere( [ 'uuid' => $this->uuidMatch ] )
                                                ->setRequestMethods( [ Request::REQUEST_TYPE_GET ] )
                                        );
                                    } )
                        );
                        $this->router->addRoute(
                            ( new RouteGroup )
                                ->setSettings( [ 'prefix' => '/v2' ] )
                                ->setCallback(
                                    function () {
                                        $this->router->addRoute(
                                            ( new RouteUrl( '/start', function () {
                                                return call_user_func( $this->callback, array_merge(
                                                    [ 'visitor', 'start', 'v2' ],
                                                    $this->apply_if_not_empty( $this->requestBody, [
                                                        'fingerprint'     => 'fingerprint',
                                                        'ip'              => 'ip',
                                                        'user_agent'      => 'user-agent',
                                                        'device'          => 'device',
                                                        'browser_name'    => 'browser-name',
                                                        'browser_version' => 'browser-version',
                                                        'lang'            => 'lang',
                                                        'url'             => 'url',
                                                        'referer'         => 'referer',
                                                        'event_name'      => 'event-name',
                                                        'event_type'      => 'event-type',
                                                    ] ),
                                                    $this->apply_attributes()
                                                ) );
                                            } ) )
                                                ->setRequestMethods( [ Request::REQUEST_TYPE_POST ] )
                                        );
                                    } )
                        );
                    }
                )
        );

        $this->router->addRoute(
            ( new RouteGroup )
                ->setSettings( [ 'middleware' => IsBackendMiddleware::class ] )
                ->setCallback(
                    function () {
                        $this->router->addRoute(
                            ( new RouteUrl( '/flow/v1/{flow_uuid}', function ( string $flowUuid ) {
                                return call_user_func( $this->callback, array_merge(
                                    [ 'get', 'flow', 'v1', $flowUuid ],
                                    $this->apply_if_not_empty( $this->router->getRequest()->getUrl()->getParams(), [ 'with' => 'with' ] )
                                ) );
                            } ) )
                                ->setWhere( [ 'flow_uuid' => $this->uuidMatch ] )
                                ->setRequestMethods( [ Request::REQUEST_TYPE_GET ] )
                        );
                        $this->router->addRoute(
                            ( new RouteUrl( '/flow/v1/{start_date}/{end_date}', function ( string $start, string $end ) {
                                return call_user_func( $this->callback, array_merge(
                                    [ 'get', 'flows', 'v1', '--start', $start, '--end', $end ],
                                    $this->apply_list_params()
                                ) );
                            } ) )
                                ->setRequestMethods( [ Request::REQUEST_TYPE_GET ] )
                        );
                        $this->router->addRoute(
                            ( new RouteUrl( '/visitor/v1/{start_date}/{end_date}', function ( string $start, string $end ) {
                                return call_user_func( $this->callback, array_merge(
                                    [ 'get', 'events', 'v1', '--start', $start, '--end', $end ],
                                    $this->apply_list_params()
                                ) );
                            } ) )
                                ->setRequestMethods( [ Request::REQUEST_TYPE_GET ] )
                        );
                        foreach (
                            [
                                'visits',
                                'domains',
                                'metrics',
                                'pages',
                                'visitors',
                                'browsers',
                                'browserversions',
                                'languages',
                                'devices',
                                'countries',
                                'cities',
                                'isps',
                                'attribute',
                                'events',
                                'events-name-type',
                                'attribute-date',
                            ]
                            as $statistic
                        ) {
                            $this->router->addRoute(
                                ( new RouteUrl( '/' . str_replace( '-', '_', $statistic ) . '/v1/{start_date}/{end_date}', function ( string $start, string $end ) use ( $statistic ) {
                                    return call_user_func( $this->callback, array_merge(
                                        [
                                            'statistics',
                                            $statistic == 'attribute' ? 'attributes' : $statistic,
                                            'v1',
                                            '--start',
                                            $start,
                                            '--end',
                                            $end
                                        ],
                                        $this->apply_list_params()
                                    ) );
                                } ) )
                                    ->setRequestMethods( [ Request::REQUEST_TYPE_GET ] )
                            );
                        }
                    }
                )
        );
    }

    private function getToken(): string {
        $auth    = $this->router->getRequest()->getHeader( 'authorization', '' );
        $matches = [];

        if ( preg_match( '/Bearer\s(\S+)/', $auth, $matches ) ) {
            return $matches[1];
        }

        return '';
    }

    private function apply_if_not_empty( array $request, array $fields ): array {
        $command = [];

        foreach ( $fields as $from => $to ) {
            if ( array_key_exists( $from, $request ) ) {
                foreach ( (array) $request[ $from ] as $one ) {
                    $command[] = '--' . $to;
                    $command[] = $one;
                }
            }
        }

        return $command;
    }

    private function apply_list_params(): array {
        $command = [];

        foreach ( $this->router->getRequest()->getUrl()->getParams() as $key => $values ) {
            $converterKey = str_replace( '_', '-', $key );

            switch ( $key ) {
                case 'page':
                case 'page_size':
                    $command[] = '--' . $converterKey;
                    $command[] = $values;
                    break;
                case 'sort':
                case 'with':
                case 'sort_direction':
                    foreach ( $values as $value ) {
                        $command[] = '--' . $converterKey;
                        $command[] = $value;
                    }
                    break;
                case 'filter':
                case 'filter_not':
                    foreach ( $values as $keyValue => $value ) {
                        if ( is_iterable( $value ) ) {
                            foreach ( $value as $v ) {
                                $command[] = '--' . $converterKey;
                                $command[] = $keyValue . '[]=' . $v;
                            }
                        } else {
                            $command[] = '--' . $converterKey;
                            $command[] = $keyValue . '=' . $value;
                        }
                    }

                    break;
            }
        }

        return $command;
    }

    private function apply_attributes(): array {
        $command = [];

        if ( ! empty( $this->requestBody['attributes'] ?? [] ) ) {
            foreach ( $this->requestBody['attributes'] as $name => $value ) {
                $command[] = '--attributes';
                $command[] = $name . '=' . $value;
            }
        }

        return $command;
    }

    public function getSimpleRouter(): Router {
        return $this->router;
    }
}