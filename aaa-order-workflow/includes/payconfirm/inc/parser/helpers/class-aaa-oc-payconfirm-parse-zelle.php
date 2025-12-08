<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/inc/parsers/class-aaa-oc-payconfirm-parse-zelle.php
 * Purpose: Parse Zelle/generic tabular emails.
 * Version: 1.0.0
 */
if ( ! defined('ABSPATH') ) { exit; }

class AAA_OC_PayConfirm_Parse_Zelle {
	public static function parse( $html, $plain, $subject = '' ) {
		$pairs = self::extract_pairs_dom( $html );
		if ( empty($pairs) ) $pairs = self::extract_pairs_text( $html );
		$map  = self::pairs_to_map( $pairs );
		$amt  = $map['amount']             ?? self::regex_amount( $html );
		$sent = $map['sent on']            ?? self::regex_date( $html );
		$txn  = $map['transaction number'] ?? self::regex_txn( $html );
		$memo = array_key_exists('memo',$map) ? trim((string)$map['memo']) : '';
		$acct = self::account_from_tail( $html );
		if ( $acct === '' && preg_match('/[A-Za-z]{2,}\s+[A-Za-z]{2,}/', $memo) ) $acct = $memo;

		return [
			'payment_method'     => 'Zelle',
			'account_name'       => trim($acct),
			'amount'             => self::to_float($amt),
			'sent_on'            => self::date_to_mysql($sent),
			'transaction_number' => preg_replace('/\D+/', '', (string)$txn ),
			'memo'               => $memo,
		];
	}

	/* ---- helpers (DOM+text) ---- */
	private static function extract_pairs_dom( $html ) {
		$html = trim($html); if ($html==='') return [];
		$dom = new DOMDocument(); libxml_use_internal_errors(true);
		$ok  = @$dom->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">'.$html);
		libxml_clear_errors(); if(!$ok) return [];
		$out=[]; foreach($dom->getElementsByTagName('tr') as $tr){ $tds=[];
			foreach($tr->childNodes as $n){ if($n instanceof DOMElement){ $t=strtolower($n->tagName); if($t==='td'||$t==='th') $tds[] = self::node_text($n);} }
			if(count($tds)>=2) $out[] = [$tds[0], $tds[count($tds)-1]];
		} return $out;
	}
	private static function node_text( DOMNode $n ){ return trim(preg_replace('/\s+/',' ',$n->textContent??'')); }
	private static function extract_pairs_text( $html ){
		$x=preg_replace('/<\/tr[^>]*>/i',"\n",$html);
		$x=preg_replace('/<\/t[dh][^>]*>/i',"\t",$x);
		$x=wp_strip_all_tags($x,true); $x=str_replace(["\r\n","\r"],"\n",$x);
		$x=preg_replace('/[ \t]+/',"\t",$x); $x=preg_replace('/\n+/',"\n",$x);
		$lines=array_filter(array_map('trim',explode("\n",$x)));
		$out=[]; foreach($lines as $line){ $parts=(strpos($line,"\t")!==false)?explode("\t",$line,2):preg_split('/\s{2,}/',$line,2);
			$lab=trim($parts[0]??''); $val=trim($parts[1]??''); if($lab!==''&&$val!=='') $out[]=[$lab,$val]; }
		return $out;
	}
	private static function pairs_to_map($pairs){ $m=[]; foreach($pairs as $p){ $lab=strtolower(trim($p[0]??'')); $val=trim($p[1]??''); if($lab!=='') $m[$lab]=$val; } return $m; }
	private static function account_from_tail($html){
		if(preg_match('/<\/table>(?!.*<\/table>)/is',$html,$m,PREG_OFFSET_CAPTURE)){ $pos=$m[0][1]+strlen($m[0][0]); $tail=wp_strip_all_tags(substr($html,$pos),true);}
		else $tail=wp_strip_all_tags($html,true);
		$tail=str_replace(["\r\n","\r"],"\n",$tail);
		$lines=array_values(array_filter(array_map('trim',explode("\n",$tail))));
		return empty($lines)?'':trim(end($lines));
	}
	private static function regex_amount($txt){ if(preg_match('/Amount\s*\$?([0-9][0-9\.,]*)/i',$txt,$m)) return $m[1]; if(preg_match('/\$\s*([0-9][0-9\.,]*)/',$txt,$m)) return $m[1]; return ''; }
	private static function regex_date($txt){ if(preg_match('/Sent\s+on\s+([A-Za-z]{3,}\s+\d{1,2},\s*\d{4})/i',$txt,$m)) return $m[1]; if(preg_match('/Date:\s+(.+?\(\w+\))/', $txt,$m)) return $m[1]; return ''; }
	private static function regex_txn($txt){ if(preg_match('/Transaction\s+number\s*([0-9][0-9\- ]*)/i',$txt,$m)) return trim($m[1]); return ''; }
	private static function date_to_mysql($human){ $human=trim((string)$human); if($human==='') return ''; $ts=strtotime($human); return $ts?gmdate('Y-m-d H:i:s',$ts):''; }
	private static function to_float($v){ $x=str_replace([',','$',' '],'',(string)$v); return is_numeric($x)?(float)$x:''; }
}
