import numeral from 'numeral';

var szCurrentLocale = 'pt-br';

numeral.register('locale', szCurrentLocale, {
    delimiters: {
        thousands: '.',
        decimal: ','
    },
    abbreviations: {
        thousand: 'mil',
        million: 'milhões',
        billion: 'b',
        trillion: 't'
    },
    ordinal: function(number) {
        return 'º';
    },
    currency: {
        symbol: 'R$'
    }
});

function parseDate(d) {
  let day = 0;
  let month = 0;
  let year = 0;

  let pattern = /(\d{1,2})[\-/ \.](\d{1,2})[\-/ \.](\d{4})/;
  let patternIso = /(\d{4})[\-/ \.](\d{1,2})[\-/ \.](\d{1,2})/;

  let result = d.match(pattern);

  //console.log( "d:", d, "result:", result );

  if (null != result && result.length > 3) {
      day = Number(result[1]);
      month = Number(result[2]);
      year = Number(result[3]);

      if (year < 1000)
          year += 2000;
  } else {
      result = d.match(patternIso);

      if (null != result && result.length > 3) {
          year = Number(result[1]);
          month = Number(result[2]);
          day = Number(result[3]);
      }
  }

  if ((day < 1 || day > 31) ||
      (month < 1 || month > 12) ||
      (year < 1970 || year > 2500))
      return null;

  return new Date(year, month - 1, day);
}


const TRACE_LEVEL = 0;
const DEBUG_LEVEL = 1;

function trace_log(){
	var args = Array.prototype.slice.call(arguments);
    
	let level = 0;
    if( args.length > 0 ) level = args.splice(0, 1);

	args.splice(0, 0, `T(${Date.now()}):` );

    if( TRACE_LEVEL >= level ) console.log.apply(null,args);
}

function debug_log(){
	var args = Array.prototype.slice.call(arguments);
    
	let level = 0;
    if( args.length > 0 ) level = args.splice(0, 1);

	args.splice(0, 0, `D(${Date.now()}):` );

    if( DEBUG_LEVEL >= level ) console.log.apply(null,args);
}

//https://stackoverflow.com/questions/901115/how-can-i-get-query-string-values-in-javascript
function getParameterByName(name, url = window.location.href) {
  name = name.replace(/[\[\]]/g, '\\$&');
  var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
      results = regex.exec(url);
  if (!results) return null;
  if (!results[2]) return '';
  return decodeURIComponent(results[2].replace(/\+/g, ' '));
}

function writeToConsole(text = "", timeout=30000) {
  let divConsole = document.getElementById("writeToconsole");

  if (null != divConsole) {
      divConsole.innerText = text;
      debug_log( 1, text );
  }

  if (text.length > 0) {
      //debug_log(2, `writeToConsole: ${text}` );
      // clear the console after showing a message for 30s
      if( timeout )
        setTimeout(writeToConsole, timeout, '');
  }
}

function utf8_to_iso8859_1(str = '') {
    let len = str.length;
    let s = '';

    debug_log( 3, "utf8_to_iso8859_1: str.length:", str.length );
    
    for (let i = 0; i < len; ++i) {
        switch ( str.charCodeAt(i) & 0xF0 ) {
            case 0xC0:
            case 0xD0:
                let c = ( (str[i].charCodeAt(i) & 0x1F) << 6) | (str.charCodeAt(++i) & 0x3F);
                s += String.fromCharCode(c); //(c < 256) ? String.fromCharCode(c) : '?';

                debug_log( 3, "case 0xC0/0xD0 c:", c, "s:", s );

                break;
            case 0xF0:
                ++i;
                // no break
            case 0xE0:
                s += '?';
                i ++;

                debug_log( 3, "case 0xF0/0xE0 i:", i, "s:", s );

                break;

            default:
                debug_log( 3, "default s:", s );
                s += str[i];
                break;
        }
    }

    return s;
}


function toTag(str = '') {
    let asciiTable = "";
    asciiTable += "\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6";
    asciiTable += "\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6";
    asciiTable += "\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6";
    asciiTable += "\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6";
    asciiTable += "\x61\x61\x61\x61\x61\x61\x61\x63\x65\x65\x65\x65\x69\x69\x69\x69";
    asciiTable += "\x64\x6e\x6f\x6f\x6f\x6f\x6f\x78\x30\x75\x75\x75\x75\x79\xa6\x62";
    asciiTable += "\x61\x61\x61\x61\x61\x61\x61\x63\x65\x65\x65\x65\x69\x69\x69\x69";
    asciiTable += "\xa6\x6e\x6f\x6f\x6f\x6f\x6f\xa6\x30\x75\x75\x75\x75\x79\xa6\x79";

    let ret = "";

    //str = utf8_decode(str);

    var combining = /[\u0300-\u036F]/g; 
    str = str.normalize('NFKD').replace(combining, '');

    for( let i=0; i< str.length; i++ ) {
        let v = str.charCodeAt(i);
        let chr = '';

        if( v >= 0x40 && v <= 0x5a )
            chr = String.fromCharCode(v+0x20);
        else if( v <= 0x2f 
            || (v >= 0x3a && v <= 0x40) 
            || (v >= 0x5b && v <= 0x60) 
            || (v >= 0x7b && v <= 0x7f) )
            chr = "\xa6";
        else if( v > 0x7f )
            chr = asciiTable[v-0x80];
        else 
            chr = str[i];

        //console.log( `str[${i}]:${str[i]} (${v}), chr:${chr}` );

        if( chr != "\xa6" )
            ret += chr;
    }

    return ret;
}

async function loadTerminais() {
    //==>debug
    debug_log(2,"loadTerminais:" );
    var pTerm = null;
    
    var tryNum = 0;
    while(pTerm == null  && ++tryNum < 6) {
        try {
            //==>debug
            debug_log(2,`loadTerminais tentativa: ${tryNum}`);

            let response = await fetch(`/atendimentos/api/terminais.php?mode=json`);

            //==>debug
            debug_log(3, 'loadTerminais->response:', response);
        
            let json = await response.json();

            //==>debug
            debug_log(2, 'loadTerminais->json:', json);

            json["tags"] = {};

            Object.keys(json).forEach(term => {
                
                //==>debug
                debug_log(3, 'loadTerminais->json->keys:', term);

                if( Number(term) > 0 ) {
                    let tags = [];

                    if( "nome" in json[term] && json[term].nome.length > 0 ) {  
                      json["tags"][toTag(json[term].nome)] = Number(term);
                    }
                    
                    if( "descricao" in json[term] && json[term].descricao.length > 0 ) {  
                      json["tags"][toTag(json[term].descricao)] = Number(term);
                    }
                         
                    if( "tags" in json[term] && json[term].tags.length > 1 ) {  
                        let arrTags = json[term].tags.split(",");
                        if( arrTags ) {
                            //==>debug
                            debug_log(3, 'loadTerminais->json->keys:', term);

                            arrTags.forEach((value) => {
                                if( value && value.length > 0 ) 
                                    json["tags"][toTag(value)] = Number(term);                
                            });
                        }
                    }
        
                    debug_log( 3, "loadTerminais: tags:", json["tags"] );
        
                }
            });

            pTerm = {...json};

        } catch(e) {
            debug_log( 1, 'loadTerminais->exception:', e);
        }
    };

    return pTerm;
}

function b64toSafeB64(str='\0') {
    var retB64 = str.replaceAll(" ","").replaceAll("\n","").replaceAll("\r","").replaceAll("/","_");

    let ctd=0;
    let pos=retB64.length-1;

    for( let i=0; i<4; i++ ) {
        if( retB64[pos-i] == '=' ) {
            ctd++;
        } else 
            break;
    }

    if( ctd > 0 )
        retB64 = retB64.substring(0,retB64.length-ctd)+`${ctd+5}`;
    else
        retB64 += ('5');

    return retB64;
}

async function loadProdutos() {
    //==>debug
    debug_log(2,"loadProdutos:" );
    var pProd = null;

    var tryNum = 0;
    while(pProd == null  && ++tryNum < 6) {
        try {
            //==>debug
            debug_log(2,`loadTerminais tentativa: ${tryNum}`);

            let response = await fetch(`/atendimentos/api/produtos.php?mode=json`);

            //==>debug
            debug_log(3, 'loadProdutos->response:', response);
        
            let json = await response.json();

            //==>debug
            debug_log(2, 'loadProdutos->json:', json);

            json["tags"] = {};

            Object.keys(json).forEach(prod => {
                //==>debug
                debug_log(3, 'loadProdutos->json->keys:', prod);

                if( Number(prod) > 0 ) {
                    if( "nome" in json[prod] && json[prod].nome.length > 0 ) {  
                        json["tags"][toTag(json[prod].nome)] = Number(prod);
                      }
                      
                      if( "descricao" in json[prod] && json[prod].descricao.length > 0 ) {  
                        json["tags"][toTag(json[prod].descricao)] = Number(prod);
                      }
                           
                      if( "tags" in json[prod] && json[prod].tags.length > 1 ) {  
                          let arrTags = json[prod].tags.split(",");
                          if( arrTags ) {
                              //==>debug
                              debug_log(3, 'loadProdutos->json->keys:', prod);
  
                              arrTags.forEach((value) => { 
                                if( value && value.length > 0 )
                                    json["tags"][toTag(value)] = Number(prod);                
                              });
                          }
                      }
          
                      debug_log( 3, "loadProdutos: tags:", json["tags"] );
                }

            });

            pProd = {...json};

        } catch(e) {
            debug_log( 1, 'loadProdutos->exception:', e);
        }
    };

    return pProd;
}

function deepCopyObject(objO) {
    //==>trace
    debug_log(2,"deepCopyObject objO:", objO, "typeof objO:", typeof objO );
    
    let retObj = _copyRecursive(objO);

    //==>debug
    debug_log(3,"deepCopyObject->retObj:", retObj );

    return retObj;
}

function _copyRecursive(objO) {
    //==>debug
    debug_log(4,"_copyRecursive->objO:", objO );

    let retObj = undefined;
    if( objO != null ) {
        if( typeof objO == "object" ) {
            if( objO instanceof Array ) {
                let o = [];
                objO.forEach( value => {
                    o.push( value );
                });
                retObj = [];
                retObj = [...o];
            } else {
                retObj = {};

                Object.keys(objO).forEach( (key) => {
                    retObj[key] = _copyRecursive(objO[key]);
                });
            }
        } else if( typeof objO == "string" ) {
            retObj = objO;
        } else if( typeof objO == "numeric" ) {
            retObj = Number(objO);
        } else 
            retObj = objO;
    } else
        retObj = "";
        
    //==>debug
    debug_log(4,"_copyRecursive->retObj:", retObj );

    return retObj;
}

var makeCRCTable = function(){
    var c;
    var crcTable = [];
    for(var n =0; n < 256; n++){
        c = n;
        for(var k =0; k < 8; k++){
            c = ((c&1) ? (0xEDB88320 ^ (c >>> 1)) : (c >>> 1));
        }
        crcTable[n] = c;
    }
    return crcTable;
}

var crc32 = function(str) {
    var crcTable = window.crcTable || (window.crcTable = makeCRCTable());
    var crc = 0 ^ (-1);

    for (var i = 0; i < str.length; i++ ) {
        crc = (crc >>> 8) ^ crcTable[(crc ^ str.charCodeAt(i)) & 0xFF];
    }

    return (crc ^ (-1)) >>> 0;
};


//const pTerminais = fetch(`/atendimentos/api/terminais.php?mode=json`)
//    .then((response) => {
//        return response.json();
//    })
//    .then(json => {
//        //==>debug
//        //console.log('objTerminais: json', json);
//
//        var objTerminais = JSON.parse(JSON.stringify(json));
//
//        objTerminais["tags"] = {};
//
//        Object.keys(objTerminais).forEach(term => {
//            if( term != "tags" ) {
//                let arrTags = objTerminais[term].tags.split(",");
//                arrTags.forEach(tag => {
//                    if (tag && tag.length > 0)
//                        objTerminais["tags"][tag] = term;
//                });
//            }
//        });
//
//        //==>debug
//        //console.log('objTerminais:', objTerminais);
//
//        return objTerminais;
//    }).catch((err) => {
//        let error = `catch: fetch Terminais: ${err.message}`;
//        writeToConsole(error);
//        var objTerminaisStatus = { 'Error':  err.message };
//        return objTerminaisStatus;
//    });

// const pProdutos = fetch(`/atendimentos/api/produtos.php?mode=json`)
//     .then(res => {
//         return res.json();
//     })
//     .then(json => {
//         //==>debug
//         //console.log("objProdutos json: ", json );

//         var objProdutos = JSON.parse(JSON.stringify(json));

//         objProdutos["tags"] = {};

//         Object.keys(objProdutos).forEach(prod => {
//             if( prod != "tags" ) {
//                 if( objProdutos[prod].tags && objProdutos[prod].tags.length > 1 ) {
//                     let arrTags = objProdutos[prod].tags.split(",");
//                     arrTags.forEach(tag => {
//                         if (tag && tag.length > 0)
//                             objProdutos["tags"][tag] = prod;
//                     });
//                 }
//             }
//         });

//         //==>debug
//         //console.log("objProdutos: ", objProdutos );

//         return objProdutos;
//     })
//     .catch((err) => {
//         let error = `catch: fetch Produtos: ${err.message}`;
//         writeToConsole(error);

//         var objProdutosStatus = { 'Error':  err.message };
//         return objProdutosStatus;
//     })

export {
    numeral, 
    szCurrentLocale, 
    parseDate, 
    getParameterByName, 
    writeToConsole, 
    utf8_to_iso8859_1,
    toTag,
    //pProdutos, pTerminais,
    trace_log, debug_log,
    loadTerminais, loadProdutos,
    deepCopyObject,_copyRecursive,
    b64toSafeB64,
    crc32,
};


/*
Promise.all([pProdutos, pTerminais]).then( async(values) => {
    //==>debug
    debug_log(3,`onLoadBody: values.length: ${values.length}`);
    debug_log(3,'onLoadBody: values:', values );

    objProdutos = {};
    if( values.length > 0 ) objProdutos = {...values[0]};

    objTerminais = {};
    if( values.length > 1 ) objTerminais = {...values[1]};

    //==>debug
    debug_log(2,"onLoadBody: objProdutos/objTerminais:", objProdutos, objTerminais);
    
    //if (objProdutosStatus.substring(0, 5) == 'Error' ||
    //    objTerminaisStatus.substring(0, 5) == 'Error') {
    //    writeToConsole(`Error loading files: objProdutosStatus:${objProdutosStatus}, objTerminaisStatus:${objTerminaisStatus}`);
    //} else {
});
*/