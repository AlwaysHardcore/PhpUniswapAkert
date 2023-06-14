#!/usr/bin/php
<?php
require "classes/Db.php";
$db = new Db;

$ltimestamp = $db->LastSwapTimestamp();
if (empty($ltimestamp)) { $ltimestamp = 0;}
#################################################################################################

$json = <<<GQL
{
    swaps(orderBy: timestamp, orderDirection: desc, where:
     { pool: "0x0254a309f5140d457c0699e2cd0457a692a69cc4",
      timestamp_gt: $ltimestamp
    }
    ) {
      pool {
        token0 {
          symbol
        }
        token1 {
          symbol
        }
      }
      sender
      recipient
      origin
      amount0
      amount1
      amountUSD
      timestamp
      id
     }
}
GQL;

$data_string = json_encode(["query" => $json]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.thegraph.com/subgraphs/name/uniswap/uniswap-v3");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Content-Length: ' . strlen($data_string))
);
$result = curl_exec($ch);

$result = json_decode($result,true);
$swaps = $result['data']['swaps'];

if (!empty($swaps)) {

    foreach($swaps as $key=>$value) { 

        $db->InsertNewSwap( $value['pool']['token0']['symbol'], 
                            $value['pool']['token1']['symbol'], 
                            $value['sender'], 
                            $value['recipient'],
                            $value['origin'], 
                            $value['amount0'], 
                            $value['amount1'], 
                            $value['amountUSD'],
                            $value['timestamp'],
                            $value['id']
                          ); 
    }

} else { echo 'No Swap';}

echo '<pre>' , var_dump($swaps), '</pre>';

echo '<pre>' , var_dump($json), '</pre>';

?>