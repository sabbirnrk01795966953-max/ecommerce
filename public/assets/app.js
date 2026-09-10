(function(){
  const uuid = (prefix='evt') => `${prefix}_${Date.now()}_${Math.random().toString(36).slice(2,10)}`;
  const money = value => `৳${Number(value || 0).toFixed(2)}`;

  window.metaEventId = uuid;
  window.trackMeta = function(eventName, customData={}, forcedId=null){
    if(!window.shopMeta?.enabled) return forcedId || uuid(eventName.toLowerCase());
    const eventId = forcedId || uuid(eventName.toLowerCase());
    try{ if(typeof fbq==='function') fbq('track', eventName, customData, {eventID:eventId}); }catch(e){}
    try{
      fetch(window.shopMeta.endpoint,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':window.shopMeta.csrf,'Accept':'application/json'},credentials:'same-origin',body:JSON.stringify({event_name:eventName,event_id:eventId,source_url:location.href,custom_data:customData}),keepalive:true});
    }catch(e){}
    return eventId;
  };

  function updateCartBadges(count){
    document.querySelectorAll('a[href*="/cart"] .badge').forEach(badge=>{ badge.textContent=String(count); });
  }

  function modalElements(){
    const modal=document.getElementById('quickCheckoutModal');
    if(!modal) return {};
    return {
      modal,
      content:modal.querySelector('[data-quick-checkout-content]'),
      loading:modal.querySelector('[data-quick-checkout-loading]')
    };
  }

  function closeQuickCheckout(){
    const {modal}=modalElements();
    if(!modal) return;
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden','true');
    document.body.classList.remove('quick-checkout-open');
  }

  function showCheckoutErrors(form, errors){
    const box=form.closest('.quick-checkout-sheet-inner')?.querySelector('[data-checkout-errors]');
    if(!box) return;
    const messages=[];
    Object.values(errors || {}).forEach(value=>{
      if(Array.isArray(value)) messages.push(...value);
      else if(value) messages.push(value);
    });
    if(!messages.length){box.hidden=true;box.innerHTML='';return;}
    box.hidden=false;
    box.innerHTML=`<b>অনুগ্রহ করে ঠিক করুন:</b><ul>${messages.map(m=>`<li>${String(m)}</li>`).join('')}</ul>`;
    box.scrollIntoView({behavior:'smooth',block:'nearest'});
  }

  function initQuickCheckoutForm(){
    const {modal}=modalElements();
    if(!modal) return;
    const form=modal.querySelector('[data-quick-checkout-form]');
    if(!form) return;
    const subtotal=Number(form.dataset.subtotal || 0);

    const refreshTotals=()=>{
      const selected=form.querySelector('[name="shipping_area"]:checked');
      const charge=Number(selected?.dataset.charge || 0);
      const shipping=form.querySelector('[data-shipping-amount]');
      const grand=form.querySelector('[data-grand-total]');
      const button=form.querySelector('[data-button-total]');
      if(shipping) shipping.textContent=money(charge);
      if(grand) grand.textContent=money(subtotal+charge);
      if(button) button.textContent=String(Math.round(subtotal+charge));
    };

    form.querySelectorAll('[name="shipping_area"]').forEach(radio=>radio.addEventListener('change',refreshTotals));
    refreshTotals();

    form.addEventListener('submit',async event=>{
      event.preventDefault();
      if(!form.reportValidity()) return;
      const submit=form.querySelector('button[type="submit"]');
      const original=submit?.innerHTML;
      if(submit){submit.disabled=true;submit.textContent='অর্ডার পাঠানো হচ্ছে...';}
      showCheckoutErrors(form,{});

      const eventId=window.metaEventId?.('purchase') || `purchase_${Date.now()}`;
      const eventInput=form.querySelector('[name="event_id"]');
      if(eventInput) eventInput.value=eventId;

      try{
        const response=await fetch(form.action,{
          method:'POST',
          headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},
          credentials:'same-origin',
          body:new FormData(form)
        });
        const data=await response.json().catch(()=>({}));
        if(!response.ok){
          showCheckoutErrors(form,data.errors || {checkout:data.message || 'অর্ডার সম্পন্ন করা যায়নি।'});
          return;
        }
        updateCartBadges(0);
        window.location.href=data.redirect || '/';
      }catch(error){
        showCheckoutErrors(form,{checkout:'নেটওয়ার্ক সমস্যার কারণে অর্ডার সম্পন্ন করা যায়নি। আবার চেষ্টা করুন।'});
      }finally{
        if(submit){submit.disabled=false;submit.innerHTML=original;}
      }
    });
  }

  async function openQuickCheckout(){
    const {modal,content,loading}=modalElements();
    if(!modal || !content || !window.storeRoutes?.quickCheckout) return;
    modal.classList.add('open');
    modal.setAttribute('aria-hidden','false');
    document.body.classList.add('quick-checkout-open');
    content.innerHTML='';
    if(loading) loading.hidden=false;

    try{
      const response=await fetch(window.storeRoutes.quickCheckout,{
        headers:{'Accept':'text/html','X-Requested-With':'XMLHttpRequest'},
        credentials:'same-origin'
      });
      if(!response.ok) throw new Error('checkout load failed');
      content.innerHTML=await response.text();
      if(loading) loading.hidden=true;
      initQuickCheckoutForm();
      const form=content.querySelector('[data-quick-checkout-form]');
      const subtotal=Number(form?.dataset.subtotal || 0);
      window.trackMeta?.('InitiateCheckout',{currency:'BDT',value:subtotal,content_type:'product'});
      setTimeout(()=>content.querySelector('input[name="customer_name"]')?.focus(),100);
    }catch(error){
      if(loading){
        loading.hidden=false;
        loading.innerHTML='চেকআউট লোড করা যায়নি। <button type="button" class="btn small" data-retry-checkout>আবার চেষ্টা করুন</button>';
        loading.querySelector('[data-retry-checkout]')?.addEventListener('click',openQuickCheckout,{once:true});
      }
    }
  }

  async function submitQuickOrder(form){
    if(form.dataset.quickSubmitting==='1') return;
    form.dataset.quickSubmitting='1';
    const button=form.querySelector('button[type="submit"],button:not([type])');
    const original=button?.innerHTML;
    if(button){button.disabled=true;button.textContent='যোগ হচ্ছে...';}

    try{
      const response=await fetch(form.action,{
        method:(form.method || 'POST').toUpperCase(),
        headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},
        credentials:'same-origin',
        body:new FormData(form)
      });
      const data=await response.json().catch(()=>({}));
      if(!response.ok) throw new Error(data.message || 'cart add failed');
      updateCartBadges(data.cart_count || 0);

      const value=Number(form.dataset.value || 0);
      const id=form.dataset.contentId;
      const qty=Number(form.querySelector('[name="quantity"]')?.value || 1);
      window.trackMeta?.('AddToCart',{
        content_ids:id?[id]:[],
        content_type:'product',
        currency:'BDT',
        value:value*qty,
        contents:id?[{id,quantity:qty,item_price:value}]:[]
      });

      await openQuickCheckout();
    }catch(error){
      const existing=document.querySelector('.quick-order-error');
      existing?.remove();
      const notice=document.createElement('div');
      notice.className='quick-order-error';
      notice.textContent='পণ্যটি কার্টে যোগ করা বা চেকআউট খোলা যায়নি। পেজ রিফ্রেশ করে আবার চেষ্টা করুন।';
      document.body.appendChild(notice);
      setTimeout(()=>notice.remove(),4500);
    }finally{
      form.dataset.quickSubmitting='0';
      if(button){button.disabled=false;button.innerHTML=original;}
    }
  }

  function initQuickOrderForms(){
    document.querySelectorAll('.quick-order-form').forEach(form=>{
      form.addEventListener('submit',event=>{
        event.preventDefault();
        submitQuickOrder(form);
      });
    });
  }

  function initQuickCheckoutClose(){
    document.addEventListener('click',event=>{
      if(event.target.closest('[data-quick-checkout-close]')) closeQuickCheckout();
      const sticky=event.target.closest('[data-submit-order-form]');
      if(sticky){
        const form=document.getElementById(sticky.dataset.submitOrderForm);
        form?.requestSubmit();
      }
    });
    document.addEventListener('keydown',event=>{
      if(event.key==='Escape') closeQuickCheckout();
    });
  }

  function initMobileStickyOrder(){
    const sticky=document.getElementById('mobileStickyOrder');
    const original=document.querySelector('.product-order-bar');
    if(!sticky || !original) return;

    let ticking=false;
    const update=()=>{
      ticking=false;
      if(window.innerWidth>720){
        sticky.classList.remove('visible');
        sticky.setAttribute('aria-hidden','true');
        return;
      }
      const rect=original.getBoundingClientRect();
      const show=rect.bottom < 0;
      sticky.classList.toggle('visible',show);
      sticky.setAttribute('aria-hidden',show?'false':'true');
    };
    const requestUpdate=()=>{
      if(ticking) return;
      ticking=true;
      requestAnimationFrame(update);
    };
    window.addEventListener('scroll',requestUpdate,{passive:true});
    window.addEventListener('resize',requestUpdate);
    update();
  }

  document.addEventListener('DOMContentLoaded',()=>{
    window.trackMeta('PageView',{});
    initQuickOrderForms();
    initQuickCheckoutClose();
    initMobileStickyOrder();
  });

  window.stepQty=function(btn,delta){
    const input=btn.parentElement.querySelector('input');
    let v=parseInt(input.value||'1',10)+delta;
    input.value=Math.max(1,Math.min(99,v));
  };
})();
