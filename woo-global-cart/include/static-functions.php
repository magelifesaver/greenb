<?php

    defined( 'ABSPATH' ) || exit;
    
    if (!function_exists('WooGC_get_domain'))
        {
            
            function WooGC_get_domain( $domain )
                {
                    
                    $original = $domain = strtolower($domain);
                    if (filter_var($domain, FILTER_VALIDATE_IP)) 
                        { 
                            return $domain; 
                        }
   
                    $icann_domains = ['accident-investigation.aero','passenger-association.aero','chirurgiens-dentistes.fr','andria-trani-barletta.it','andria-barletta-trani.it','air-traffic-control.aero','accident-prevention.aero','andriatranibarletta.it','andriabarlettatrani.it','experts-comptables.fr','air-surveillance.aero','education.tas.edu.au','groundhandling.aero','huissier-justice.fr','travelersinsurance','northwesternmutual','geometre-expert.fr','civilaviation.aero','entertainment.aero','schools.nsw.edu.au','certification.aero','championship.aero','workinggroup.aero','parachuting.aero','association.aero','maintenance.aero','hanggliding.aero','ascoli-piceno.it','campinagrande.br','nieruchomosci.pl','plurinacional.bo','paragliding.aero','recreation.aero','catholic.edu.au','navigation.aero','rotorcraft.aero','tempio-olbia.it','gs.jan-mayen.no','consulting.aero','consultant.aero','conference.aero','americanexpress','ascolipiceno.it','production.aero','microlight.aero','sandvikcoromant','kerryproperties','airtraffic.aero','government.aero','journalist.aero','ballooning.aero','federation.aero','modelling.aero','saobernardo.br','scientist.aero','cancerresearch','alessandria.it','gs.svalbard.no','cookingchannel','veterinaire.fr','afamilycompany','tempioolbia.it','kerrylogistics','skydiving.aero','pharmaciens.km','veterinaire.km','bananarepublic','profesional.bo','cooperativa.bo','weatherchannel','logistics.aero','americanfamily','aerodrome.aero','aerobatic.aero','ambulance.aero','amusement.aero','equipment.aero','emergency.aero','homebuilt.aero','insurance.aero','pharmacien.fr','educator.aero','movimiento.bo','aeroclub.aero','catering.aero','tecnologia.bo','transporte.bo','travelchannel','santamaria.br','parliament.cy','wolterskluwer','chernivtsi.ua','alto-adige.it','parliament.nz','fylkesbibl.no','santoandre.br','spreadbetting','chernovtsy.ua','research.aero','exchange.aero','software.aero','magazine.aero','lifeinsurance','services.aero','international','democracia.bo','engineer.aero','realestate.pl','aircraft.aero','lplfinancial','jan-mayen.no','chernigov.ua','council.aero','folkebibl.no','construction','gyeongnam.kr','suedtirol.it','control.aero','chambagri.fr','scholarships','aparecida.br','chernihiv.ua','turystyka.pl','in-addr.arpa','pordenone.it','charter.aero','cherkassy.ua','industria.bo','joinville.br','journal.aero','gyeongbuk.kr','trading.aero','airline.aero','leasing.aero','express.aero','trainer.aero','freight.aero','altoadige.it','gliding.aero','airport.aero','agrigento.it','versicherung','riobranco.br','embaixada.st','consulado.st','student.aero','repbody.aero','economia.bo','ecologia.bo','foodnetwork','lamborghini','indigena.bo','rightathome','academia.bo','chungbuk.kr','barclaycard','piacenza.it','investments','redumbrella','williamhill','oristano.it','contractors','enterprises','engineering','ingatlan.hu','konyvelo.hu','creditunion','calvinklein','medicina.bo','progressive','contagem.br','chungnam.kr','accountants','bridgestone','design.aero','gyeonggi.kr','ribeirao.br','riopreto.br','salvador.br','playstation','broker.aero','siracusa.it','saogonca.br','curitiba.br','campinas.br','olayangroup','boavista.br','aeroport.fr','sorocaba.br','productions','agents.aero','principe.st','notaires.fr','author.aero','verbania.it','vercelli.it','politica.bo','noticias.bo','notaires.km','londrina.br','svalbard.no','kerryhotels','safety.aero','motorcycles','cherkasy.ua','brindisi.it','blockbuster','trader.aero','blackfriday','avellino.it','engine.aero','flight.aero','photography','mitsubishi','ciencia.bo','realestate','ravenna.it','kommune.no','saotome.st','maringa.br','agrinet.tn','venezia.it','defense.tn','management','potenza.it','onyourside','boehringer','nextdirect','niteroi.br','trapani.it','grondar.za','treviso.it','jeonbuk.kr','pistoia.it','barreau.bj','tourism.pl','eurovision','cuisinella','technology','accountant','bolivia.bo','swiftcover','barueri.br','jeonnam.kr','sondrio.it','floripa.br','industries','gs.oslo.no','sassari.it','incheon.kr','salerno.it','immobilien','goiania.br','gwangju.kr','cargo.aero','newholland','avocat.pro','deporte.bo','tourism.tn','medecin.fr','nationwide','extraspace','revista.bo','gangwon.kr','daejeon.kr','natural.bo','taranto.it','schaeffler','brescia.it','empresa.bo','commune.am','act.edu.au','pescara.it','trieste.it','africa.com','protection','volkswagen','university','bnpparibas','associates','republican','vlaanderen','consulting','tas.edu.au','vistaprint','properties','prudential','vic.edu.au','perugia.it','restaurant','foundation','qld.gov.au','basketball','erotica.hu','erotika.hu','qld.edu.au','tatamotors','ekloges.cy','palermo.it','vic.gov.au','media.aero','group.aero','tas.gov.au','creditcard','healthcare','nsw.edu.au','apartments','works.aero','union.aero','medecin.km','capitalone','pilot.aero','press.aero','es.gov.br','presse.ci','ce.gov.br','rj.gov.br','ba.gov.br','arezzo.it','ap.gov.br','df.gov.br','ac.gov.br','rn.gov.br','am.gov.br','ro.gov.br','rr.gov.br','statefarm','fortal.br','pr.gov.br','stockholm','mt.gov.br','rs.gov.br','microsoft','ma.gov.br','furniture','mg.gov.br','ms.gov.br','al.gov.br','go.gov.br','pb.gov.br','fujixerox','pe.gov.br','pi.gov.br','fuel.aero','melbourne','pa.gov.br','kuokgroup','sc.gov.br','recife.br','school.za','osasco.br','palmas.br','accenture','landrover','marshalls','verona.it','marketing','presse.km','pramerica','school.na','presse.ml','varese.it','ancona.it','aquila.it','travelers','police.uk','venice.it','directory','trento.it','morena.br','frontdoor','richardli','se.gov.br','sp.gov.br','to.gov.br','panasonic','fresenius','health.nz','goldpoint','teramo.it','statebank','torino.it','lancaster','firestone','macapa.br','maceio.br','manaus.br','financial','vacations','show.aero','scjohnson','museum.no','wa.gov.au','sa.edu.au','ragusa.it','museum.mw','casino.hu','shangrila','powiat.pl','mincom.tn','dgca.aero','museum.mv','jogasz.hu','wa.edu.au','club.aero','sa.gov.au','padova.it','allfinanz','homesense','ltd.co.im','hisamitsu','musica.bo','homegoods','aquarelle','plc.co.im','bloomberg','szkola.pl','travel.pl','travel.tt','naples.it','homedepot','museum.tt','ap.gov.pl','ic.gov.pl','solutions','barcelona','is.gov.pl','us.gov.pl','institute','alfaromeo','napoli.it','community','novara.it','equipment','nombre.bo','patria.bo','caxias.br','gov.nc.tr','crew.aero','rimini.it','insurance','ponpes.id','musica.ar','idrett.no','fairwinds','savona.it','analytics','rovigo.it','nt.edu.au','education','amsterdam','recht.pro','chieti.it','cuiaba.br','miasta.pl','lifestyle','pueblo.bo','reklam.hu','health.vn','iris.arpa','museum.om','9guacu.br','edunet.tn','school.nz','tozsde.hu','e164.arpa','utazas.hu','passagens','avoues.fr','avocat.fr','christmas','computer','commbank','parma.it','ericsson','padua.it','fidelity','maserati','mckinsey','nuoro.it','delivery','frontier','firmdale','media.hu','memorial','redstone','verisign','engineer','press.se','parti.se','symantec','video.hu','reliance','capetown','lakas.hu','vanguard','hotel.hu','games.hu','forum.hu','agrar.hu','sport.hu','perso.ht','football','lundbeck','brand.se','ventures','adult.ht','pavia.it','diamonds','gs.tr.no','gs.hm.no','store.ro','training','prato.it','cleaning','cityeats','herad.no','gs.bu.no','gs.rl.no','terni.it','gs.fm.no','download','allstate','gs.hl.no','acct.pro','democrat','greta.fr','gs.mr.no','rieti.it','name.fj ','gs.nl.no','cipriani','aland.fi','saarland','deloitte','gs.nt.no','maori.nz','lighting','gs.of.no','gs.ol.no','clinique','exchange','gmina.pl','sklep.pl','media.pl','discount','pharmacy','press.cy','observer','aoste.it','esurance','feedback','aosta.it','discover','etisalat','catering','catholic','clothing','gs.va.no','gs.sf.no','gs.aa.no','gs.vf.no','belau.pw','gs.tm.no','supplies','targi.pl','gs.st.no','marriott','udine.it','gs.ah.no','turin.it','abudhabi','samsclub','airforce','siena.it','business','attorney','hdfcbank','helsinki','mortgage','feira.br','plumbing','seoul.kr','partners','hotel.tz','ulsan.kr','property','graphics','woodside','perso.sn','caa.aero','security','holdings','software','grainger','hospital','softbank','urn.arpa','hotel.lk','infiniti','uri.arpa','store.bb','boutique','bradesco','broadway','barefoot','brussels','budapest','services','carrd.co','radio.br','sampa.br','pictures','salud.bo','busan.kr','jampa.br','guardian','agric.za','store.st','daegu.kr','bargains','baseball','istanbul','natal.br','belem.br','showtime','perso.tn','store.nf','jpmorgan','other.nf','ipiranga','shopping','goodyear','stcgroup','anani.br','store.ve','press.ma','merckmsd','ip6.arpa','tksat.bo','barclays','turen.tn','res.aero','builders','comcast','contact','college','blog.bo','info.pk','cologne','tiffany','mobi.gp','exposed','company','philips','coop.tt','aaa.pro','sandvik','stat.no','info.bo','compare','prof.pr','info.pr','aero.mv','info.tt','coop.mv','citadel','mycd.eu','samsung','name.pr','arte.bo','winners','agro.bo','priv.pl','monster','from.hr','hamburg','weather','watches','name.mv','name.tr','website','temasek','asso.gp','shop.pl','pisa.it','wanggou','hangout','info.tr','asso.bj','wedding','xfinity','guam.gu','info.mv','tickets','pioneer','auto.pl','info.la','windows','info.gu','coop.mw','gouv.bj','whoswho','theatre','theater','clubmed','info.pl','mail.pl','walmart','okinawa','asso.ht','name.hr','network','hotmail','kiwi.nz','support','hoteles','hosting','surgery','lviv.ua','kyiv.ua','neustar','govt.nz','krym.ua','firm.in','shiksha','schmidt','info.ls','info.au','conf.au','muni.il','info.ve','geek.nz','cruises','asti.it','asso.mc','name.mk','netbank','priv.me','gouv.ml','lincoln','limited','shriram','science','firm.ve','schwarz','digital','info.nr','singles','arts.ve','dentist','netflix','conf.lv','domains','holiday','mex.com','android','name.tt','2000.hu','priv.hu','country','info.hu','info.bb','systems','gouv.ht','corsica','coop.ht','city.hu','cooking','aero.tt','oslo.no','hitachi','mobi.tt','info.ht','firm.ht','shop.ht','jobs.tt','bolt.hu','film.hu','cricket','info.az','jpn.com','info.vn','mobi.tz','courses','desa.id','coupons','name.vn','info.tz','bauhaus','assn.lk','szex.hu','suli.hu','shop.hu','alibaba','name.az','news.hu','club.tw','ebiz.tw','game.tw','express','realtor','aca.pro','mobi.na','gouv.km','vlog.br','wiki.br','name.cy','grocery','asso.km','coop.km','lanxess','hyundai','auction','careers','info.ni','asso.nc','finance','univ.sn','fishing','gouv.sn','bugatti','lasalle','name.ng','abogado','arts.ro','firm.ro','info.ro','politie','info.ec','asso.dz','brother','academy','info.na','ferrero','coop.rw','markets','name.na','staples','avianca','taxi.br','auspost','ferrari','fitness','mobi.ng','asso.re','mobi.ke','capital','kitchen','firm.nf','gallery','info.nf','rentals','athleta','info.ke','fhsk.se','genting','asso.ci','metlife','storage','recipes','juniper','gouv.ci','info.zm','arts.nf','fujitsu','name.jo','reviews','info.sd','organic','info.co','origins','firm.co','rexroth','arts.co','flights','florist','komatsu','flowers','info.ki','lacaixa','godaddy','jewelry','forsale','caravan','frogans','booking','audible','trading','latrobe','info.fj','priv.no','bentley','roma.it','rome.it','toshiba','info.et','med.pro','name.et','name.my','coop.br','banamex','farmers','publ.pt','blog.br','law.pro','nome.pt','guitars','port.fr','wiki.bo','rnrt.tn','kids.us','bestbuy','info.tn','agro.pl','intl.tn','bar.pro','cpa.pro','eng.pro','gouv.fr','jur.pro','asso.fr','leclerc','chintai','isla.pr','agakhan','name.tj','charity','coop.py','name.qa','jeju.kr','name.eg','test.tj','channel','fashion','ismaili','riik.ee','oldnavy','flog.br','com.mo','com.mx','vgs.no','org.mo','org.mw','net.mk','net.mo','net.mw','net.nf','web.nf','int.mw','rec.nf','per.nf','org.mn','gov.mw','web.ni','edu.mo','com.nf','gov.mo','edu.mw','shouji','search','aramco','gov.mn','mil.mz','edu.mk','gov.mk','com.mv','inf.mk','gob.mx','biz.mv','edu.mz','com.ml','mil.mg','com.mg','natura','edu.mv','edu.ml','gov.ml','org.mx','net.ml','beauty','gov.mz','org.ml','dep.no','com.ng','edu.ng','fhs.no','edu.mx','gov.ng','net.mx','anquan','edu.mn','org.ni','secure','com.mw','int.ni','biz.ni','org.mt','nissan','com.ni','edu.ni','gob.ni','org.mv','nissay','select','net.mv','norton','org.na','mil.my','sch.ng','com.na','com.mu','mil.ni','net.mu','org.mu','gov.mu','author','mil.mv','net.ni','adv.mz','museum','int.mv','nom.nc','org.mk','biz.mw','net.my','gov.mr','net.mz','nowruz','org.mz','mil.ng','com.ms','mil.no','com.my','edu.ms','gov.mv','gov.ms','net.ms','nom.ni','edu.my','org.ms','org.my','bayern','com.mk','online','net.ng','com.mt','gov.my','pro.na','edu.mt','net.mt','org.ng','pro.mv','org.tn','amazon','com.tj','gov.tl','web.tj','org.tj','nic.tj','net.tj','mil.tj','int.tj','gov.tj','edu.tj','biz.tj','edu.ws','alt.za','net.th','edu.za','gov.za','law.za','mil.za','net.za','ngo.za','nic.za','com.tm','org.tm','org.sz','net.tn','mil.to','edu.to','org.to','net.to','gov.to','com.to','org.ws','rnu.tn','rns.tn','nat.tn','net.tm','ind.tn','gov.tn','fin.tn','ens.tn','com.tn','gov.ws','edu.tm','mil.tm','gov.tm','nom.tm','nis.za','nom.za','bbs.tr','com.sn','biz.ss','biz.zm','org.so','net.so','gov.so','edu.so','com.so','org.sn','edu.sn','art.sn','edu.ss','org.sl','gov.sl','edu.sl','net.sl','com.sl','com.zm','quebec','edu.zm','racing','com.ss','gov.ss','org.za','org.sv','org.sy','com.sy','mil.sy','net.sy','gov.sy','edu.sy','gov.sx','web.za','red.sv','gob.sv','net.ss','edu.sv','com.sv','otsuka','org.st','net.st','mil.st','gov.st','edu.st','com.st','org.ss','net.ws','bel.tr','org.sh','gob.ve','com.ug','gov.vc','mil.vc','edu.vc','pfizer','physio','com.ve','e12.ve','edu.ve','gov.ve','photos','int.ve','mil.ve','net.ve','org.ve','rec.ve','tec.ve','web.ve','com.vi','k12.vi','org.ug','org.vc','org.vi','nsn.us','com.uy','edu.uy','gub.uy','mil.uy','net.uy','org.uy','com.uz','net.uz','org.uz','isa.us','net.vc','fed.us','dni.us','sch.uk','plc.uk','org.uk','nhs.uk','net.uk','com.vc','ltd.uk','gov.uk','net.vi','com.vn','biz.tr','tel.tr','biz.tt','net.tt','org.tt','com.tt','edu.vu','net.vu','org.vu','web.tr','tsk.tr','pol.tr','int.tt','org.tr','net.tr','kep.tr','k12.tr','mil.tr','gov.tr','gen.tr','edu.tr','com.ws','com.tr','pro.tt','gov.tt','net.vn','biz.vn','org.vn','org.ua','net.ua','edu.vn','gov.ua','edu.ua','com.ua','gov.vn','int.vn','mil.tz','edu.tt','pro.vn','pictet','idv.tw','org.tw','net.tw','com.tw','mil.tw','gov.tw','edu.tw','com.vu','mil.sh','gov.sh','biz.nr','gov.pk','aid.pl','org.pl','net.pl','com.pl','gos.pk','gop.pk','gon.pk','gok.pk','gob.pk','web.pk','atm.pl','biz.pk','fam.pk','org.pk','edu.pk','net.pk','com.pk','mil.ph','ngo.ph','edu.ph','com.ac','biz.pl','org.ph','edu.pn','pro.pr','edu.pr','gov.pr','org.pr','net.pr','com.pr','sakura','olayan','net.pn','org.pn','edu.pl','airbus','gov.pn','gov.pl','airtel','sos.pl','sex.pl','rel.pl','nom.pl','mil.pl','gsm.pl','gov.ph','net.ph','est.pr','gen.nz','med.om','gov.om','edu.om','com.om','office','org.nz','net.nz','mil.nz','iwi.nz','cri.nz','org.om','alsace','alstom','school','schule','com.nr','net.nr','org.nr','edu.nr','gov.nr','net.om','pro.om','com.ph','gob.pe','sanofi','edu.pf','org.pf','com.pf','net.pe','com.pe','org.pe','mil.pe','nom.pe','edu.pe','gob.pa','alipay','nom.pa','med.pa','abo.pa','ing.pa','net.pa','edu.pa','sld.pa','org.pa','com.pa','biz.pr','safety','net.sh','com.sc','edu.sd','org.sd','net.sd','com.sd','orange','edu.sc','org.sc','net.sc','gov.sc','org.sb','abarth','net.sb','gov.sb','edu.sb','com.sb','abbott','sch.sa','edu.sa','pub.sa','med.sa','med.sd','gov.sd','org.sa','realty','com.sh','gov.zm','per.sg','edu.sg','gov.sg','org.sg','net.sg','com.sg','mil.zm','net.zm','org.zw','org.zm','sch.zm','org.se','reisen','repair','report','fhv.se','gov.zw','mil.zw','review','gov.sa','net.sa','agency','int.pt','org.py','net.py','mil.py','gov.py','edu.py','com.py','africa','ryukyu','com.pt','edu.pt','edu.qa','org.pt','gov.pt','net.pt','net.ps','org.ps','com.ps','plo.ps','sec.ps','gov.ps','edu.ps','com.qa','gov.qa','com.sa','edu.rs','rocher','org.rw','net.rw','mil.rw','gov.rw','abbvie','oracle','org.rs','gov.rs','rogers','mil.qa','www.ro','rec.ro','org.ro','nom.ro','com.ro','nom.re','com.re','sch.qa','org.qa','net.qa','edu.mg','claims','prd.mg','giving','net.ci','george','juegos','edu.ci','com.ci','org.ci','viking','villas','virgin','gov.cd','kaufen','vision','global','joburg','jaguar','google','gov.bz','edu.bz','org.bz','net.bz','int.ci','viajes','com.by','org.cn','edu.co','com.co','flickr','kosher','futbol','kindle','gallup','kinder','mil.cn','net.cn','gov.cl','gov.cn','edu.cn','com.cn','garden','net.cm','gov.cm','com.cm','mil.cl','gob.cl','com.bz','mil.by','int.co','ppg.br','slg.br','sjc.br','rio.br','rec.br','qsl.br','pvh.br','psi.br','psc.br','pro.br','poa.br','srv.br','org.br','ong.br','odo.br','ntr.br','not.br','nom.br','net.br','mus.br','mil.br','slz.br','teo.br','gov.by','org.bs','gratis','org.bw','org.bt','net.bt','gov.bt','edu.bt','com.bt','gov.bs','edu.bs','net.bs','the.br','com.bs','voting','zlg.br','vix.br','vet.br','udi.br','tur.br','trd.br','tmp.br','gov.co','mil.co','mat.br','gov.ee','com.eg','latino','fie.ee','gov.mg','aip.ee','pri.ee','com.ee','med.ee','lib.ee','edu.ee','eun.eg','mil.ec','gob.ec','gov.ec','edu.ec','org.ec','pro.ec','med.ec','k12.ec','fin.ec','edu.eg','gov.eg','com.ec','gov.et','mil.fj','gov.fj','com.fj','biz.fj','lawyer','net.et','biz.et','edu.et','org.et','com.et','mil.eg','edu.es','gob.es','org.es','nom.es','com.es','toyota','sci.eg','org.eg','net.eg','net.ec','art.dz','net.co','inf.cu','gov.cy','com.cy','biz.cy','gov.cx','org.cw','net.cw','edu.cw','com.cw','unicom','gov.cu','net.cy','net.cu','org.cu','edu.cu','com.cu','lancia','web.co','rec.co','org.co','nom.co','ltd.cy','org.cy','pol.dz','net.do','edu.dz','gov.dz','net.dz','org.dz','com.dz','travel','web.do','sld.do','org.do','mil.do','pro.cy','gov.do','gob.do','edu.do','com.do','art.do','gov.dm','edu.dm','org.dm','net.dm','com.dm','med.br','lel.br','org.fj','qc.com','nsw.au','act.au','asn.au','gov.au','edu.au','org.au','net.au','com.au','no.com','ru.com','tas.au','gov.as','sa.com','se.net','tur.ar','org.ar','net.ar','mil.ar','int.ar','gov.ar','qld.au','vic.au','edu.ar','biz.az','biz.bb','hu.net','org.ba','net.ba','mil.ba','gov.ba','edu.ba','com.ba','jp.net','pro.az','com.aw','mil.az','hockey','edu.az','org.az','gov.az','int.az','net.az','com.az','kr.com','gob.ar','com.ar','edu.bb','org.ae','net.af','org.af','com.af','gov.af','gr.com','in.net','mil.ae','gov.ae','sch.ae','net.ae','com.ag','imamat','us.org','nom.ad','co.com','org.ac','mil.ac','net.ac','gov.ac','edu.ac','edu.af','org.ag','uk.com','mil.al','uk.net','hotels','us.com','org.am','net.am','com.am','uy.com','org.al','net.al','gov.al','net.ag','edu.al','com.al','hughes','org.ai','net.ai','com.ai','off.ai','za.com','nom.ag','com.bb','gov.bb','leg.br','bhz.br','def.br','cri.br','com.br','cnt.br','cng.br','cim.br','bsb.br','bmd.br','bio.br','ato.br','eco.br','art.br','arq.br','aju.br','agr.br','adv.br','adm.br','abc.br','voyage','web.bo','ecn.br','edu.br','net.bo','ggf.br','jus.br','jor.br','jdf.br','jab.br','inf.br','ind.br','imb.br','gru.br','gov.br','g12.br','emp.br','fst.br','foz.br','fot.br','fnd.br','far.br','eti.br','etc.br','esp.br','eng.br','mil.bo','org.bo','net.bb','br.com','health','insure','hermes','hiphop','xihuan','crd.co','uwu.ai','ae.org','ar.com','cn.com','intuit','com.de','com.se','de.com','eu.com','gov.bf','gb.com','gb.net','hu.com','org.bb','webcam','com.bh','int.bo','org.bm','gob.bo','edu.bo','com.bo','vuelos','org.bn','net.bn','gov.bn','edu.bn','com.bn','net.bm','edu.bh','gov.bm','edu.bm','com.bm','walter','org.bi','edu.bi','com.bi','gov.bh','org.bh','net.bh','net.fj','org.ee','pro.fj','edu.ls','church','circle','org.lr','net.lr','moscow','luxury','biz.ls','gov.ls','gov.lr','net.ls','clinic','org.ls','gov.lt','social','family','coffee','soccer','chrome','madrid','condos','caseih','com.jo','studio','com.jm','ltd.lk','org.je','net.je','career','mattel','casino','edu.lr','market','grp.lk','center','mormon','com.lr','makeup','maison','chanel','comsec','com.lv','soc.lk','int.is','design','med.ly','org.ly','direct','abr.it','edu.it','gov.it','doctor','org.is','dental','gov.is','edu.is','com.is','net.is','net.lc','org.ir','net.ma','gov.ma','living','sch.ly','coupon','com.ly','credit','edu.lv','gov.lv','org.lv','cruise','mil.lv','net.lv','asn.lv','london','edu.ly','dating','net.ly','datsun','gov.ly','locker','dealer','plc.ly','degree','web.lk','org.jo','org.ma','ind.kw','org.ky','com.ky','gov.ky','edu.ky','mobile','org.kw','net.kw','gov.kw','monash','emb.kw','edu.kw','com.kw','gov.lc','gov.lk','bostik','boston','mil.kr','net.ky','org.kz','tra.kp','gov.la','org.lb','net.lb','gov.lb','edu.lb','com.lb','org.la','com.la','per.la','edu.la','edu.kz','net.la','int.la','org.lc','edu.lc','com.kz','mil.kz','gov.kz','net.kz','broker','rep.kp','net.jo','org.kg','net.ki','biz.ki','edu.ki','mil.kg','gov.kg','edu.kg','com.kg','net.kg','org.lk','gov.ki','camera','edu.lk','ngo.lk','stream','mil.jo','gov.jo','sch.jo','edu.jo','org.ki','com.ki','org.kp','int.lk','gov.kp','edu.kp','com.kp','sch.lk','net.lk','gov.kn','edu.kn','org.kn','net.kn','org.km','com.km','ass.km','mil.km','edu.km','com.lk','prd.km','gov.km','nom.km','net.ir','sch.ir','mutual','ind.gt','gov.gu','edu.gu','com.gu','estate','org.gt','net.gt','mil.gt','gob.gt','org.gu','edu.gt','com.gt','gov.gr','org.gr','net.gr','edu.gr','com.gr','org.gp','net.gu','web.gu','net.gp','net.hk','net.hn','org.hn','edu.hn','com.hn','gov.ir','target','org.hk','idv.hk','tattoo','gov.hk','edu.hk','com.hk','org.gy','net.gy','gov.gy','edu.gy','com.gy','edu.gp','com.gp','gob.hn','gov.ge','org.gg','net.gg','expert','pvt.ge','net.ge','mil.ge','org.ge','edu.ge','com.gh','com.ge','tjmaxx','tkmaxx','cci.fr','lefrak','prd.fr','nom.fr','com.fr','tienda','edu.gh','tennis','com.gl','net.gn','org.gn','gov.gn','edu.gn','com.gn','org.gl','net.gl','edu.gl','events','gov.gh','org.gi','edu.gi','mod.gi','gov.gi','ltd.gi','com.gi','mil.gh','org.gh','mil.hn','taobao','com.lc','org.im','ind.in','gen.in','org.in','net.in','swatch','dunlop','net.im','edu.in','com.im','dupont','com.hr','org.il','net.il','k12.il','idf.il','nic.in','res.in','gov.me','com.io','supply','net.iq','org.iq','com.iq','mil.iq','edu.iq','gov.iq','bharti','gov.in','eu.int','nagoya','net.me','suzuki','org.me','edu.me','mil.in','gov.il','durban','edu.ht','org.hu','energy','emerck','rel.ht','mil.id','art.ht','med.ht','org.ht','pro.ht','biz.id','net.id','net.ht','com.ht','pol.ht','nom.mg','org.mg','taipei','sch.id','sex.hu','web.id','berlin','sydney','gov.ie','its.me','bingo','co.ve','ok.us','movie','bible','nadex','boats','ut.us','oh.us','co.vi','tn.us','or.us','wv.us','horse','honda','house','sd.us','photo','black','sc.us','ri.us','wy.us','beats','vi.us','phone','hyatt','pr.us','co.uz','wi.us','party','wa.us','va.us','vt.us','pa.us','tx.us','codes','homes','iveco','dubai','gifts','gives','glade','glass','globo','gmail','jetzt','drive','lilly','edeka','co.zm','green','gripe','ac.zm','linde','lipsy','group','delta','lixil','earth','osaka','loans','epson','faith','lease','aetna','adult','omega','legal','fedex','actor','lexus','final','ac.zw','lamer','kyoto','forex','forum','koeln','gallo','email','co.zw','games','deals','locus','ac.vn','build','nokia','mango','media','guide','ninja','cards','canon','irish','miami','nikon','chase','audio','autos','intel','nexus','azure','bosch','baidu','parts','money','paris','cheap','tm.za','gucci','dance','lotte','dabur','lotto','cymru','amfam','crown','amica','lupin','nowtv','cisco','coach','cloud','co.za','apple','click','ac.za','nv.us','archi','citic','macys','ny.us','co.mg','nm.us','fm.it','ch.it','ci.it','cl.it','cn.it','co.it','cr.it','cs.it','ct.it','cz.it','en.it','fc.it','fe.it','fg.it','fi.it','fr.it','cb.it','li.it','me.it','mc.it','mb.it','lu.it','lt.it','lo.it','le.it','ge.it','lc.it','kr.it','is.it','im.it','gr.it','go.it','ce.it','ca.it','mn.it','al.it','swiss','ac.il','co.il','ac.im','co.im','tt.im','tv.im','co.in','ac.in','ac.ir','co.ir','id.ir','sucks','ag.it','an.it','bz.it','bi.it','bt.it','bs.it','br.it','bo.it','bn.it','bl.it','bg.it','ao.it','ba.it','av.it','at.it','ar.it','aq.it','ap.it','mi.it','mo.it','my.id','vr.it','ta.it','te.it','tn.it','to.it','tp.it','tr.it','ts.it','tv.it','ud.it','va.it','nj.us','vc.it','ve.it','vi.it','vs.it','ss.it','co.jp','or.jp','ne.jp','lg.jp','gr.jp','go.jp','ed.jp','ad.jp','vt.it','ac.jp','store','study','co.je','style','vv.it','sv.it','sr.it','ms.it','po.it','mt.it','na.it','no.it','nu.it','og.it','or.it','ot.it','pa.it','pc.it','pd.it','pe.it','pg.it','pi.it','pn.it','pr.it','sp.it','ri.it','so.it','si.it','sa.it','ro.it','rn.it','rm.it','rg.it','pt.it','re.it','rc.it','ra.it','pz.it','pv.it','pu.it','or.id','go.id','co.ke','ns.ca','fm.br','mp.br','tc.br','tv.br','volvo','co.bw','vodka','of.by','ab.ca','bc.ca','mb.ca','nb.ca','nf.ca','nl.ca','nt.ca','tv.bo','co.ci','co.cl','video','md.ci','go.ci','ac.ci','ed.ci','or.ci','nu.ca','gc.ca','yk.ca','sk.ca','qc.ca','pe.ca','on.ca','am.br','wales','ac.cn','or.at','co.ae','ac.ae','co.ag','za.bz','co.am','ed.ao','gv.ao','og.ao','co.ao','pb.ao','it.ao','ac.at','co.at','gv.at','id.au','or.bi','xerox','co.bi','watch','weber','weibo','works','world','ac.be','oz.au','tv.bb','co.bb','pp.az','wa.au','sa.au','nt.au','co.cm','ah.cn','co.id','ac.fj','fi.cr','go.cr','or.cr','sa.cr','ubank','tushu','ac.cy','tm.cy','tunes','trust','trade','tours','total','toray','tools','co.cr','ac.gn','ac.id','tm.hu','co.hu','iz.hr','co.gy','tatar','co.gl','tokyo','co.gg','tires','tirol','tm.fr','tmall','today','ed.cr','ac.cr','bj.cn','hb.cn','js.cn','jl.cn','hn.cn','hl.cn','hi.cn','he.cn','ha.cn','ln.cn','gx.cn','gz.cn','gs.cn','gd.cn','fj.cn','cq.cn','jx.cn','nm.cn','vegas','xj.cn','tw.cn','mo.cn','hk.cn','zj.cn','yn.cn','xz.cn','tj.cn','nx.cn','sx.cn','sn.cn','sh.cn','sd.cn','sc.cn','qh.cn','ac.ke','vb.it','go.ke','place','ac.sz','praxi','poker','ac.th','co.th','go.th','in.th','mi.th','or.th','ac.tj','co.tj','go.tj','co.tm','pizza','press','av.tr','dr.tr','tv.tr','nc.tr','co.tt','ac.tz','co.tz','go.tz','me.tz','ne.tz','or.tz','sc.tz','tv.tz','in.ua','co.sz','prime','cn.ua','rocks','ac.pr','co.pw','ne.pw','or.pw','ed.pw','go.pw','rugby','nt.ro','tm.ro','ac.rs','co.rs','in.rs','rodeo','ac.rw','co.st','co.rw','me.ke','tv.sd','ac.se','bd.se','fh.se','reise','pp.se','rehab','tm.se','radio','quest','me.so','promo','ck.ua','cr.ua','salon','in.us','az.us','ca.us','co.us','ct.us','dc.us','de.us','fl.us','ga.us','gu.us','hi.us','ia.us','id.us','il.us','ks.us','ar.us','ky.us','la.us','ma.us','md.us','me.us','mi.us','mn.us','mo.us','ms.us','mt.us','nc.us','nd.us','ne.us','nh.us','as.us','al.us','cv.ua','sb.ua','dn.ua','dp.ua','if.ua','kh.ua','km.ua','kr.ua','ks.ua','kv.ua','lg.ua','lt.ua','lv.ua','mk.ua','od.ua','rv.ua','sm.ua','ak.us','te.ua','uz.ua','vn.ua','zp.ua','zt.ua','co.ug','or.ug','ac.ug','sc.ug','go.ug','ne.ug','ac.uk','co.uk','me.uk','co.pn','ricoh','tm.pl','ac.mz','skype','co.me','ac.me','tm.mg','shoes','shell','ac.mu','co.mu','or.mu','sharp','ac.mw','co.mw','seven','co.mz','tm.mc','pc.pl','or.na','dr.na','us.na','mx.na','ca.na','in.na','cc.na','tv.na','ws.na','co.na','sener','ac.ni','co.ni','sling','smart','aa.no','or.kr','ne.ke','or.ke','sc.ke','tm.km','stada','ac.kr','co.kr','es.kr','go.kr','hs.kr','kg.kr','ms.kr','ne.kr','pe.kr','ac.ma','re.kr','sc.kr','sport','space','co.lc','solar','ac.lk','ac.ls','co.ls','sc.ls','id.lv','id.ly','smile','co.ma','in.ni','ikano','sf.no','co.nz','onion','mr.no','nl.no','co.om','of.no','ol.no','ac.nz','hm.no','rl.no','ha.no','vf.no','va.no','tr.no','tm.no','st.no','ac.pa','nt.no','fm.no','hl.no','bu.no','ah.no','voto','song','lgbt','limo','immo','wang','sohu','imdb','maif','meet','meme','sony','mini','vote','sncf','open','sale','menu','vivo','talk','itau','rmit','moto','sarl','weir','life','saxo','surf','like','live','mint','ollo','ruhr','link','rsvp','room','spot','wien','save','star','moda','lego','xbox','lidl','work','wine','safe','wiki','prod','skin','taxi','play','kiwi','pink','loft','read','pars','nico','raid','town','kpmg','kred','nike','toys','page','rent','tube','plus','ltda','qpon','seek','seat','scot','pohl','vana','porn','prof','love','land','reit','next','java','visa','team','jeep','tech','scor','teva','ieee','site','sina','navy','silk','pccw','viva','show','news','shop','jprs','luxe','shia','rich','tiaa','kddi','loan','tips','shaw','pics','rest','sexy','ping','c.la','p.se','call','cash','case','casa','mobi','cars','care','camp','name','cern','cafe','i.ng','buzz','book','i.ph','post','bond','bofa','cbre','jobs','blog','dclk','duck','coop','docs','dish','diet','desi','dell','deal','date','chat','data','cyou','aarp','cool','info','club','city','citi','blue','a.se','erni','arab','t.se','auto','audi','asda','arte','u.se','army','w.se','r.se','x.se','y.se','z.se','amex','ally','akdn','aigo','adac','s.se','baby','bing','beer','b.se','c.se','d.se','bike','e.se','f.se','g.se','best','h.se','band','i.se','k.se','l.se','bbva','m.se','n.se','o.se','bank','dvag','able','n.bg','z.bg','w.bg','hair','haus','x.bg','g.bg','game','y.bg','hdfc','help','guru','fund','0.bg','1.bg','2.bg','free','here','hgtv','3.bg','f.bg','v.bg','guge','d.bg','m.bg','p.bg','gmbh','q.bg','r.bg','s.bg','gold','gift','ggee','l.bg','h.bg','gent','golf','t.bg','goog','gbiz','u.bg','k.bg','j.bg','i.bg','o.bg','e.bg','c.bg','fast','fido','host','hsbc','8.bg','arpa','icbc','farm','film','9.bg','fans','b.br','fail','fage','aero','asia','fiat','5.bg','food','fire','6.bg','flir','7.bg','ford','a.bg','fish','4.bg','b.bg','ovh','ott','xxx','pay','aaa','pet','tel','pid','phd','xin','ren','pin','qvc','pwc','pub','ice','pnc','red','pru','vip','sas','wtf','edu','stc','wed','int','tab','tax','tci','gov','thd','tjx','top','trv','spa','tui','tvs','ubs','uno','uol','biz','ups','com','vet','vig','vin','srl','soy','wtc','sbi','wow','ril','rio','rip','run','rwe','pro','wme','sap','cat','sbs','sky','sca','scb','org','net','win','ses','sew','sex','sfr','mil','ski','tdk','icu','abb','dds','cfd','cpa','ltd','crs','lpl','csc','dad','lol','day','llp','man','llc','dev','dhl','diy','dnp','dog','dot','dtv','dvr','eat','cfa','abc','esq','bzh','moi','moe','boo','mma','mls','mlb','bot','mit','box','buy','cab','ceb','cal','men','cam','car','med','mba','cba','cbn','cbs','map','eco','eus','bom','got','jll','jio','gmo','gmx','jcp','jcb','goo','itv','gop','ist','jnj','hbo','hiv','ink','ing','hkt','inc','hot','how','ibm','ifm','jmp','jot','fan','ftr','lds','law','lat','fit','fly','foo','fox','krd','kpn','frl','fun','joy','fyi','gal','kim','kia','kfh','gap','gay','gdn','gea','gle','mom','ceo','ngo','aeg','onl','anz','ong','aol','nab','now','bid','bet','bcn','nba','nec','ads','bcg','app','nyc','bbt','afl','aig','bbc','art','new','bar','off','aws','obi','axa','nfl','nhk','aco','one','mtr','ntt','nra','ooo','bmw','bio','nrw','bms','mtn','mov','msd','cw','by','do','dz','cg','ec','cf','cd','cc','sx','ca','bz','ee','sj','bw','bv','eg','bt','bs','er','sm','sy','br','bo','dm','dk','cu','de','cr','cx','so','sr','cv','bn','cy','co','cz','sl','cn','sv','cm','sk','ss','dj','cl','ck','st','ci','sn','ch','su','rw','tk','bm','au','wf','ws','yt','ye','bg','bf','be','bd','bb','ba','az','ax','aw','at','vn','zm','as','zw','ar','aq','ao','am','al','ai','ag','af','ae','ad','vu','vi','bj','to','sz','bi','bh','tc','td','tf','tg','th','tj','et','tl','tm','tn','tr','vg','tt','tv','tw','tz','ua','ug','uk','us','uy','uz','va','vc','ve','es','gh','eu','mp','ne','nc','na','mz','my','mx','mw','mv','mu','mt','ms','mr','mq','mo','ng','mn','mm','ml','mk','mh','mg','me','md','mc','ma','ly','lv','lu','lt','nf','ni','lr','pl','rs','ro','re','qa','py','sa','sb','pw','pt','ps','pr','pn','pm','pk','nl','ph','sc','pg','sd','pf','pe','se','pa','om','nz','nu','nr','np','no','ls','lk','fi','gm','hm','hk','gy','gw','gu','gt','gs','gr','gq','gp','sg','sh','gn','gl','hr','gi','ru','gg','gf','ge','gd','gb','si','ga','fr','fo','fm','fk','fj','hn','ht','li','ke','lc','lb','la','kz','ky','kw','kr','kp','kn','km','ki','kh','kg','jp','hu','jo','jm','je','it','is','ir','iq','io','ac','in','im','il','ie','id','hk'];
                                                        
                    $matches = array_filter( $icann_domains, function($var) use ( $domain ) 
                        { 
                            return preg_match("/\b" . $var ."$/i", $domain); }
                    );
                    
                    if ( count  ( $matches ) > 0 )
                        {
                            reset ( $matches );
                            $domain_extension   =   current ( $matches );   
                            
                            $domain = str_replace( "." . $domain_extension, "", $domain);
                            $arr    = explode( '.', $domain );
                            
                            $arr    =   (array)$arr[ count ( $arr ) - 1 ];
                            $arr[]  =   $domain_extension;
                            
                            return join('.', $arr);
                        }
                    
                    $arr = array_slice(array_filter(explode('.', $domain, 4), function($value){
                        return $value !== 'www';
                    }), 0); //rebuild array indexes
                        
                    if (count($arr) > 2)
                        {
                            $count = count($arr);
                            $_sub = explode('.', $count === 4 ? $arr[3] : $arr[2]);

                            if (count($_sub) === 2) // two level TLD
                            {
                                $removed = array_shift($arr);
                                if ($count === 4) // got a subdomain acting as a domain
                                {
                                    $removed = array_shift($arr);
                                }
                            }
                            elseif (count($_sub) === 1) // one level TLD
                            {
                                $removed = array_shift($arr); //remove the subdomain
                                if (strlen($_sub[0]) === 2 && $count === 3) // TLD domain must be 2 letters
                                {
                                    array_unshift($arr, $removed);
                                }
                                else
                                {
                                    // non country TLD according to IANA
                                    $tlds = array(
                                        'aero',
                                        'arpa',
                                        'asia',
                                        'biz',
                                        'cat',
                                        'com',
                                        'coop',
                                        'edu',
                                        'gov',
                                        'info',
                                        'jobs',
                                        'mil',
                                        'mobi',
                                        'museum',
                                        'name',
                                        'net',
                                        'org',
                                        'post',
                                        'pro',
                                        'tel',
                                        'travel',
                                        'xxx',
                                    );
                                    if (count($arr) > 2 && in_array($_sub[0], $tlds) !== false) //special TLD don't have a country
                                    {
                                        array_shift($arr);
                                    }
                                }
                            }
                            else // more than 3 levels, something is wrong
                            {
                                for ($i = count($_sub); $i > 1; $i--)
                                {
                                    $removed = array_shift($arr);
                                }
                            }
                        }
                    elseif (count($arr) === 2)
                        {
                            $arr0 = array_shift($arr);
                            if (strpos(join('.', $arr), '.') === false
                                && in_array($arr[0], array(
                                                                //'localhost',
                                                                //'test',
                                                                'invalid'
                                                                )) === false) // not a reserved domain
                            {
                                // seems invalid domain, restore it
                                array_unshift($arr, $arr0);
                            }
                        }
                        
                    return join('.', $arr);
                    
                }   
            
        }


    
    if ( ! function_exists( 'woocommerce_account_orders' ) ) 
        {

            /**
             * My Account > Orders template.
             *
             * @param int $current_page Current page number.
             */
            function woocommerce_account_orders( $current_page ) 
                {
                    if (    !   class_exists('WooGC_Template'))
                        {
                            $current_page    = empty( $current_page ) ? 1 : absint( $current_page );
                            $customer_orders = wc_get_orders(
                                apply_filters(
                                    'woocommerce_my_account_my_orders_query',
                                    array(
                                        'customer' => get_current_user_id(),
                                        'page'     => $current_page,
                                        'paginate' => true,
                                    )
                                )
                            );

                            wc_get_template(
                                'myaccount/orders.php',
                                array(
                                    'current_page'    => absint( $current_page ),
                                    'customer_orders' => $customer_orders,
                                    'has_orders'      => 0 < $customer_orders->total,
                                )
                            );
                            
                            
                            return ;
                            
                        }
                        
                    $WooGC_Template =   new WooGC_Template();
                    $WooGC_Template->woocommerce_account_orders( $current_page );
                    
                }
        }
    
    
    
    /**
    * Allow a way for other plugins/custom codes to support MultiSite products    
    */
    add_action( 'woocommerce/cart_loop/start',  'woogc_woocommerce_cart_loop_start');
    add_action( 'woocommerce/cart_loop/end',    'woogc_woocommerce_cart_loop_end');
    function woogc_woocommerce_cart_loop_start( $data )
        {
            if ( is_array( $data )  &&  isset ( $data['blog_id'] ) )
                switch_to_blog( $data['blog_id'] );
            else if ( is_object( $data )  &&  isset ( $data->blog_id ) )
                switch_to_blog( $data->blog_id );
            else if ( $data instanceof WC_Order_Item_Product )
                {
                    $blog_id    =   $data->get_meta('blog_id', TRUE);
                    if ( $blog_id > 0 )
                        switch_to_blog( $blog_id );   
                }
            else if ( $data instanceof WC_Order )
                {
                    $blog_id    =   $data->get_meta('blog_id', TRUE);
                    if ( $blog_id > 0 )
                        switch_to_blog( $blog_id );   
                }
        }
        
    function woogc_woocommerce_cart_loop_end( $data )
        {
            if ( ( is_array( $data )  &&  isset ( $data['blog_id'] ) )  ||  ( is_object( $data )  &&  isset ( $data->blog_id ) ) )
                restore_current_blog();
            else if ( $data instanceof WC_Order_Item_Product )
                {
                    $blog_id    =   $data->get_meta('blog_id', TRUE);
                    if ( $blog_id > 0 )
                        restore_current_blog();  
                }
            else if ( $data instanceof WC_Order )
                {
                    $blog_id    =   $data->get_meta('blog_id', TRUE);
                    if ( $blog_id > 0 )
                        restore_current_blog();  
                }
        }

?>