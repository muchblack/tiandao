<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use LINE\Constants\HTTPHeader;
use LINE\Parser\EventRequestParser;
use LINE\Parser\Exception\InvalidEventRequestException;
use LINE\Parser\Exception\InvalidSignatureException;


class LineBotWebHook
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->header(HTTPHeader::LINE_SIGNATURE);
        if(empty($signature))
        {
            return \response("Bed Request", 400);
        }
        try{
            $secret = config('line.channel_secret');
            EventRequestParser::parseEventRequest($request->getContent(), $secret, $signature);
        }catch (InvalidSignatureException $e){
            return \response("Bed Request", 400);
        }catch (InvalidEventRequestException $e)
        {
            return \response("Invalid event request", 400);
        }

        return $next($request);
    }
}
