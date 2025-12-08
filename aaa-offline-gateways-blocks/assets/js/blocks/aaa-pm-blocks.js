/* AAA OGB – Blocks tip UI (v1.4.2) */
/* File: /wp-content/plugins/aaa-offline-gateways-blocks/assets/js/blocks/aaa-pm-blocks.js */
/* File Version: 1.4.2 */
(function () {
  const reg=(window.wc&&(window.wc.wcBlocksRegistry||window.wc.blocksRegistry))||window.wcBlocksRegistry||null;
  const {registerPaymentMethod}=reg||{};
  const wcSettings=(window.wc&&window.wc.wcSettings)||window.wcSettings;
  const wpEl=window.wp?.element||null;
  const el=wpEl?.createElement, useState=wpEl?.useState, useEffect=wpEl?.useEffect;
  const select=window.wp?.data?.select, subscribe=window.wp?.data?.subscribe;
  const dispatcher=window.wp?.data?.dispatch?.('wc/store/checkout');
  const checkoutAPI=window.wc?.blocksCheckout||null;
  if(!registerPaymentMethod||!wcSettings||!el||!useState){
    console.warn('[AAA-OGB][TIP] Blocks deps missing');
    return;
  }

  const IDS=['pay_with_zelle','pay_with_venmo','pay_with_applepay','pay_with_creditcard','pay_with_cashapp','pay_with_cod'];
  const STORE='wc/store';
  const TIP_MAP={};
  let last = getSelectedPM();
  let tipWarningActive = false;

  function log(){ try{ console.log.apply(console,arguments); }catch(e){} }
  function roundToHalf(x){ const n=parseFloat(x); if(!isFinite(n)||n<0) return 0; return Math.round(n*2)/2; }

  async function fetchCart(){
    try{
      const r=await fetch(((wcSettings?.storeApi?.root)||'/wp-json/wc/store/v1/')+'cart',{credentials:'same-origin'});
      return await r.json();
    }catch{ return null }
  }
  function pushCart(c){
    try{
      const d=window.wp?.data?.dispatch?.(STORE);
      if(d?.receiveCart){ d.receiveCart(c); return true }
    }catch{}
    return false
  }
  function refreshTotals(){
    fetchCart().then(c=>{
      const ok=c&&pushCart(c);
      log('[AAA-OGB][TIP] refreshTotals called, pushed=',ok);
    });
  }
  async function syncTip(pm,cents){
    try{
      log('[AAA-OGB][TIP] syncTip → gateway=',pm,' cents=',cents);
      if(checkoutAPI?.extensionCartUpdate){
        await checkoutAPI.extensionCartUpdate({namespace:'aaa-ogb/tip',data:{pm,tip_cents:cents}});
      }else{
        const url=window.AAA_OGB_AJAX?.url,nonce=window.AAA_OGB_AJAX?.nonce;
        const body=new URLSearchParams({action:'aaa_pm_apply_tip',nonce,tip:(cents/100),pm}).toString();
        await fetch(url,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body,credentials:'same-origin'});
        refreshTotals();
      }
    }catch(e){ console.error('[AAA-OGB][TIP] syncTip error',e); }
  }
  function getSelectedPM(){
    try{
      const c=select?.('wc/store/checkout');
      const v=c?.getSelectedPaymentMethod?.()||c?.getSelectedPaymentMethodId?.()||c?.getPaymentMethodId?.();
      if(typeof v==='string') return v;
      if(v?.paymentMethodId) return v.paymentMethodId;
      if(v?.id) return v.id;
    }catch{}
    const r=document.querySelector('input[name="radio-control-wc-payment-method-options"]:checked');
    return (r&&r.value)||'';
  }
  function hasTipWithLabel(label){
    try{
      const fees=(select?.(STORE)?.getCartTotals()?.fees)||[];
      return !!fees.find(f=>f?.name===label && Number(f?.total_amount||0)>0);
    }catch{ return false }
  }

  /* Watch for payment method switching */
  if(!window.__AAA_OGB_SWITCH__){
    window.__AAA_OGB_SWITCH__=true;
    setInterval(()=>{
      const now = getSelectedPM();
      if(now && now!==last){
        const lastTip = Number(TIP_MAP[last]||0);
        const lastHasTip = lastTip > 0;

        const cfg = wcSettings.getSetting(now + '_data', {});
        const tipEnabled = cfg?.tipping?.enabled === true;

        if(lastHasTip && !tipEnabled && !tipWarningActive){
          tipWarningActive = true;
          showTipWarning(last, now, lastTip, ()=>{ tipWarningActive = false; });
          return;
        }

        last = now;
        const cents=Number(TIP_MAP[now]||0);
        syncTip(now,cents);
      }
    },400);
  }

  function showTipWarning(prevGateway, newGateway, cents, done){
    const amount=(cents/100).toFixed(2);
    const overlay=document.createElement('div');
    overlay.className='aaa-tip-warning-overlay';
    overlay.innerHTML=`
      <div class="aaa-tip-warning">
        <p>You’ve added a tip of $${amount}. This payment method does not accept tips.</p>
        <p>If you continue, your tip will be removed.</p>
        <div class="aaa-tip-warning-buttons">
          <button class="button button-primary proceed">Proceed</button>
          <button class="button cancel">Go Back</button>
        </div>
      </div>`;
    document.body.appendChild(overlay);

    overlay.querySelector('.proceed').addEventListener('click',()=>{
      TIP_MAP[prevGateway]=0;
      if(window.__AAA_OGB_STATE__?.[prevGateway]){
        window.__AAA_OGB_STATE__[prevGateway].tip='';
      }
      syncTip(newGateway,0);
      if(dispatcher?.setSelectedPaymentMethod){
        dispatcher.setSelectedPaymentMethod(newGateway);
      }
      log('[AAA-OGB][TIP] User chose Proceed, cleared tip, switching to',newGateway);
      overlay.remove();
      last=newGateway;
      done && done();
    });

    overlay.querySelector('.cancel').addEventListener('click',()=>{
      if(dispatcher?.setSelectedPaymentMethod){
        dispatcher.setSelectedPaymentMethod(prevGateway);
      }
      log('[AAA-OGB][TIP] User chose Go Back, reverted to',prevGateway);
      overlay.remove();
      done && done();
    });
  }

  function UI(id,settings){
    return function Component(){
      const [,setTick]=useState(0);
      const bump=()=>setTick(t=>t+1);
      const sym=(wcSettings.currency&&wcSettings.currency.symbol)||'$';
      const feeLabel=`Tip (${settings.title})`;
      const STATE=(window.__AAA_OGB_STATE__=window.__AAA_OGB_STATE__||{});
      const s=(STATE[id]=STATE[id]||{tip:settings?.tipping?.default||'',applied:false});

      useEffect(()=>{
        const sync=()=>{
          const now=hasTipWithLabel(feeLabel);
          if(now!==s.applied){
            s.applied=now;
            bump();
            log('[AAA-OGB][TIP] Cart updated, fee present=',now,' gateway=',id);
          }
        };
        sync();
        const unsub=subscribe?.(sync);
        return ()=>unsub&&unsub();
      },[]);

      const presets=(settings.tipping?.presets||'')
        .split(',').map(v=>v.trim()).filter(Boolean)
        .map((v,i)=>
          el('button',{key:i,type:'button',className:'button',
            onClick:()=>{
              s.tip=parseFloat(v)||0;
              bump();
              log('[AAA-OGB][TIP] Preset clicked=',s.tip,' gateway=',id);
            },
            style:{marginRight:'6px'}},sym+Number(v).toFixed(0))
        );

      const onApply=async()=>{
        const rounded=roundToHalf(s.tip);
        const cents=Math.max(0,Math.round(rounded*100));
        TIP_MAP[id]=cents;
        s.applied=cents>0;
        s.tip=rounded;
        bump();
        log('[AAA-OGB][TIP] Apply clicked → tip=',rounded,' gateway=',id);
        await syncTip(id,cents);
      };
      const onRemove=async()=>{
        s.tip='';
        TIP_MAP[id]=0;
        s.applied=false;
        bump();
        log('[AAA-OGB][TIP] Remove clicked → gateway=',id);
        await syncTip(id,0);
      };

      const showRemove=!!s.applied || (Number(TIP_MAP[id]||0)>0);

      return el('div',{},
        settings.tipping?.enabled?el('div',{className:'aaa-tip'},
          el('label',{},'Tip Your Driver'),
          presets.length?el('div',{style:{margin:'6px 0'}},...presets):null,
          el('div',{style:{display:'flex',gap:'6px',alignItems:'center'}},
            el('input',{type:'number',min:'0',step:'0.5',
              value:(s.tip??''),onChange:e=>{
                s.tip=e.target.value;
                bump();
                log('[AAA-OGB][TIP] Input changed=',s.tip,' gateway=',id);
              },style:{width:'110px'}}),
            el('button',{type:'button',className:'button button-primary',onClick:onApply},'Apply tip'),
            showRemove?el('button',{type:'button',className:'button',onClick:onRemove},'Remove tip'):null
          )
        ):null,
        settings.description?el('div',{className:'aaa-desc',dangerouslySetInnerHTML:{__html:settings.description}}):null
      );
    };
  }

  IDS.forEach(id=>{
    const cfg=wcSettings.getSetting(id+'_data',{})||{};
    const ok=!!cfg&&!!cfg.title;
    log('[AAA-OGB][TIP] register method=',id,' enabled=',cfg?.tipping?.enabled);
    if(!ok) return;
    const Comp=UI(id,cfg);
    registerPaymentMethod({
      name:id,label:el('span',{},cfg.title),ariaLabel:cfg.title,canMakePayment:()=>true,
      supports:{features:['products']},content:el(Comp),edit:el(Comp),paymentMethodId:id,
      onPaymentSetup:()=>{
        const raw=(window.__AAA_OGB_STATE__?.[id]?.tip)||'';
        const rounded=roundToHalf(raw);
        return {paymentMethodData:{ tip_amount:String(rounded) }};
      }
    });
  });
})();
