import './style.css';

import { numeral,szCurrentLocale,getParameterByName,writeToConsole, 
    trace_log,debug_log,_copyRecursive,toTag} 
    from '../lib/helper_functions.js';

var objTerminal = {};
var savedObjTerminal = {};

var terminal_id = 0;
var changes = 0;

const changeBits = {
  "new": 0xffff,
  "nome": 1<<0,
  "descricao": 1<<1,
  "tags": 1<<2,
};

function onChangeForm1(evt) {
  let fldName = evt.target.name.toUpperCase();
  let fldValue = evt.target.value;

  let pattern = /^([A-Z]+)_?(\d*)_?(\d*)/;
  let fldBaseName = fldName.match(pattern);

  writeToConsole();

  //==>debug
  debug_log(2, "onChangeForm1 => fldName:", fldName, "fldBaseName:", fldBaseName, "fldValue:", fldValue, "evt.target:", evt.target);

  if( fldBaseName.length > 1 ) {
    //==>debug
    debug_log(3,"onChangeForm1->fldBaseName[1]:", fldBaseName[1] );

    switch (fldBaseName[1]) {
        case "NOME": 
        {
          objProdutos.nome = fldValue;
          evt.target.value = objTerminal.nome;

          let a = objTerminal.nome;
          let b = savedObjTerminal.nome;

          if( ((!b || b.length == 0) &&
            (a.trim().length > 0 )) || 
            ((b && b.length > 0) && 
            (a.toLowerCase().trim() != b.toLowerCase().trim()))) {
            changes |= changeBits.nome;
          } else {
            changes &= ~changeBits.nome;
          }

          break;
        }
        case "DESCRICAO": 
        {
          objTerminal.descricao = fldValue;
          evt.target.value = objTerminal.descricao;

          let a = objTerminal.descricao;
          let b = savedObjTerminal.descricao;

          if( ((!b || b.length == 0) &&
            (a.trim().length > 0 )) || 
            ((b && b.length > 0) && 
            (a.toLowerCase().trim() != b.toLowerCase().trim()))) {
            changes |= changeBits.descricao;
          } else {
            changes &= ~changeBits.descricao;
          }

          break;
        }
        case "TAGS": 
        {
          objTerminal.tags = fldValue;
          evt.target.value = objTerminal.tags;

          let a = objTerminal.tags;
          let b = savedObjTerminal.tags;

          if( ((!b || b.length == 0) &&
            (a.trim().length > 0 )) || 
            ((b && b.length > 0) && 
            (a.toLowerCase().trim() != b.toLowerCase().trim()))) {
            changes |= changeBits.tags;
          } else {
            changes &= ~changeBits.tags;
          }

          break;
        }
      }

    if( changes > 0 ) {
      debug_log( 1, "changes: ", changes.toString(2) );
      disableButton(false);
    } else {
      disableButton(true);
    }
  }
}

async function sendJson(json, apiURL) {
  //==>trace
  trace_log(1,"sendJson:", json, apiURL );

  let id = (json && json.terminal_id) ? json.terminal_id : 0;
  let action = (id == 0) ? "POST" : "PUT";

  //==>debug
  debug_log(1,"sendJson json:", json);
  debug_log(1,"sendJson apiURL:", apiURL);

  let headers = new Headers();

  headers.append('Accept', 'application/json');
  headers.append("Content-type", "application/json");

  const response = await fetch( apiURL, {
      headers: headers,
      mode: 'cors', // no-cors, *cors, same-origin
      body: JSON.stringify(json),
      method: action, // *GET, POST, PUT, DELETE, etc.
      cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
      credentials: 'same-origin', // include, *same-origin, omit
      redirect: 'follow', // manual, *follow, error
      referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
  });

  //==>debug
  debug_log(2,"sendJson response:", response);

  if( response.ok ) {
      return {
          "error": false,
          "response": await response.json(), 
      };
  } else {     
      return {
          "error": true,
          "response": response,
      }; 
  }
}

async function onSaveForm1(evt) {
  //==>trace
  trace_log(1,"onSaveForm1 evt:", evt);

  let objChanges = {};
  let id = (objTerminal && objTerminal.terminal_id) ? objTerminal.terminal_id : 0;
  let isUpdate = false;

  if( id == 0 ) 
    changes = changeBits.new;

  if( changes > 0 ) {
      //==>debug
    debug_log(1, "onSaveForm1: id: ", id, "changes: ", changes.toString(2) );

    objChanges["terminal_id"] = id;

    if( (changes & changeBits.nome) > 0  ) {
      objChanges["nome"] = objTerminal.nome;
    }
   
    if( (changes & changeBits.descricao) > 0  ) {
      objChanges["descricao"] = objTerminal.descricao;
    }

    if( (changes & changeBits.tags) > 0  ) {
      objChanges["tags"] = objTerminal.tags;
    }

    //==>debug
    debug_log(2,"onSaveForm1->objChanges:", objChanges);

    if (id > 0) { // Update
      isUpdate = true;

      debug_log(3, `onSaveForm1->update: changes: ${changes}` );
    } else {
      debug_log(3, `onSaveForm1->add new:` );
    }

    const response = await sendJson(objChanges, `/atendimentos/api/terminais.php`);

    //==>debug
    debug_log( 2, "onSaveForm1:sendJson:response:", response );

    if( response.error ) {
      let errMsg = `onSaveForm1:sendJson(${isUpdate?"update":"Add New"}):response.error: ${JSON.stringify(response.response)}`;
      writeToConsole(errMsg);
      debug_log( 1, errMsg );
    } else {
      const json = response.response;

      if (json["error"]) {
          let errMsg = `onSaveForm1:sendJson(${isUpdate?"update":"Add New"}):json["error"]: ${JSON.stringify(json["error"])}`;
          writeToConsole(errMsg);
          console.error(errMsg);
      } else {
        //==>debug
        debug_log(3, 'onSaveForm1:sendJson(${isUpdate?"update":"Add New"}):json:', json );

        if (id != json.terminal_id) {
          //==>debug
          debug_log(2,`onSaveForm1: id(${id})!=json.terminal_id(${json.terminal_id})`);
          
          objTerminal = await loadTerminal(json.terminal_id);
        }

        terminal_id = objTerminal.terminal_id;
        id = terminal_id;

        savedObjTerminal = JSON.parse(JSON.stringify(objTerminal));
        disableButton(true);
        changes = 0;
    
        crateMainApp();
      
        if( isUpdate )
          writeToConsole(`Updated (${terminal_id})!`, 5000);
        else
          writeToConsole(`Saved New produto (${terminal_id})!`, 5000);    
      }
    }
  }
}

function disableButton(status = true) {
  //==>trace
  trace_log(1, "disableButton:", status);

  let bt = document.getElementById("salvar");
  
  if( bt )
      bt.disabled = (terminal_id==0)?false:status;
}

// Main App
function crateMainApp() {
  const mainApp = document.getElementById("MainApp");
  mainApp.innerText = "";

  const frmForm1 = document.createElement("FORM");
  frmForm1.id = "form1";
  frmForm1.enctype = "multipart/form-data";

  frmForm1.onchange = function(evt) {
      return onChangeForm1(evt);
  };

  // terminal_id
  let divNode; 
  let inputNode;
  let label;
  let pNode;
  
  let fieldSet = document.createElement("FIELDSET");
  let legend = document.createElement("LEGEND");
  legend.innerText = `Terminal id: ${terminal_id&&terminal_id>0?terminal_id:'New'}`;
  fieldSet.appendChild(legend);

  pNode = document.createElement("P");
  label = document.createElement("LABEL");
  label.innerHTML = `<label for="email">Nome:</label>`
  pNode.appendChild(label);

  inputNode = document.createElement("INPUT");
  inputNode.name = "nome";
  inputNode.type = "text";
  inputNode.id = "nome";
  inputNode.size = "30";
  inputNode.maxlength = "30";
  inputNode.value = (objTerminal && objTerminal.nome)?objTerminal.nome:"";
  pNode.appendChild(inputNode);

  fieldSet.appendChild(pNode);

  pNode = document.createElement("P");
  label = document.createElement("LABEL");
  label.innerHTML = `<label for="email">Descrição:</label>`
  pNode.appendChild(label);
  inputNode = document.createElement("INPUT");
  inputNode.name = "descricao";
  inputNode.type = "text";
  inputNode.id = "descricao";
  inputNode.size = "50";
  inputNode.maxlength = "50";
  inputNode.value = (objTerminal && objTerminal.descricao)?objTerminal.descricao:"";
  pNode.appendChild(inputNode);

  fieldSet.appendChild(pNode);

  pNode = document.createElement("P");
  label = document.createElement("LABEL");
  label.innerHTML = `<label for="email">Tags:</label>`
  pNode.appendChild(label);
  inputNode = document.createElement("INPUT");
  inputNode.name = "tags";
  inputNode.type = "text";
  inputNode.id = "tags";
  inputNode.size = "50";
  inputNode.maxlength = "150";
  inputNode.value = (objTerminal && objTerminal.tags)?objTerminal.tags:"";
  pNode.appendChild(inputNode);

  fieldSet.appendChild(pNode);

  // button
  divNode = document.createElement("DIV");
  inputNode = document.createElement("INPUT");
  inputNode.type = "button";
  inputNode.id = "salvar";

  inputNode.disabled = (terminal_id&&terminal_id>0)?true:false;

  if (terminal_id && Number(terminal_id) > 0)
    inputNode.value = "Save";
  else
    inputNode.value = "Add New";

  inputNode.onclick = function(evt) {
    onSaveForm1(evt);
  };

  divNode.appendChild(inputNode);

  fieldSet.appendChild(divNode);

  frmForm1.appendChild(fieldSet);

  mainApp.appendChild(frmForm1);
}

async function loadTerminal(terminal_id) {

  return new Promise((resolve, reject) => {
    
    //==>debug
    debug_log(2, "loadTerminal fetch call:", `/atendimentos/api/terminais.php?id=${terminal_id}&mode=json`);

    let headers = new Headers();
    headers.append('Accept', 'application/json');

    fetch(`/atendimentos/api/terminais.php?id=${terminal_id}&mode=json`, {
            mode: 'cors', // no-cors, *cors, same-origin
            method: "GET", // *GET, POST, PUT, DELETE, etc.
            headers: headers,
            cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
            credentials: 'same-origin', // include, *same-origin, omit
            redirect: 'follow', // manual, *follow, error
            referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        })
        .then((response) => {
            return response.json();
        })
        .then(json => {

            //==>debug
            debug_log(2,'loadTerminals json:', json);

            if (json.error) {
                writeToConsole(`loadTerminals error: ${JSON.stringify(json.error)}`);
                reject(json.error);
            }

            objTerminal = JSON.parse(JSON.stringify(json[terminal_id]));
            objTerminal["terminal_id"] = terminal_id;

            savedObjTerminal = JSON.parse(JSON.stringify(objTerminal));

            //==>debug
            debug_log(2, 'loadTerminal (response) objTerminal:', objTerminal);
            
            crateMainApp();

            resolve(objTerminal);
        })
        .catch((err) => {
            let error = `catch: fetch objTerminal: ${err.message}`;
            writeToConsole(error);
            reject(error);
        });
    });
}

(async function onLoadBody() {
  //==>trace
  trace_log(1,"onLoadBody:" );

  numeral.locale(szCurrentLocale);
  
  writeToConsole();
  terminal_id = getParameterByName('terminal_id');

  if( Number(terminal_id) > 0) {
    objTerminal = await loadTerminal(terminal_id);
  }

  //==>debug
  debug_log(2, "onLoadBody: terminal_id:", terminal_id);

  crateMainApp();
})();
