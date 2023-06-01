<?php

use App\CartRequest;
use App\Rating;

function showRate($requestID){

	

	$request = CartRequest::where('id', $requestID)->first();
	
	$data = "";
	
	if($request){

		$r = Rating::where('order_id', $request->id)->first();

		if($r){
			$order = $r->rate_order;
			$service = $r->rate_service;
		}else{
		
			$order = 0;
			$service = 0;
		}

		

		$script = '$("#order-rate").rateYo({"rating": "'.$order.'", starWidth: "20px", ratedFill: "#ffc107", normalFill: "#dadada"});';
		$script .= '$("#service-rate").rateYo({"rating": "'.$service.'", starWidth: "20px", ratedFill: "#ffc107", normalFill: "#dadada"});';


		$data .= "<script>".$script."</script>";
		
		$data .= '<input type="hidden" name="requestId" value="'.$request->id.'">';
		
		$data .= '<div class="row">
	            
	
	            <div class="col-md-4">
	              <label style="color:#55BFA3">قيم المنتج</label>
                <div style="clear: both;">
                    <div class="rating stars">
                        <div id="order-rate"></div>
                    </div>
                </div>
	            </div>
	            
	            <div class="col-md-4">
	               <label style="color:#55BFA3">قيم الخدمة</label>
                <div style="clear: both;">
                    <div class="rating stars">
                        <div id="service-rate"></div>
                    </div>
                </div>
	            </div>

        	</div>';	

	}	
                  
return [$data];
                           
}


