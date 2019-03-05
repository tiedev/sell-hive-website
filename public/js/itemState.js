
function itemStateWasCreated(currentItemState) {
  return _itemStateWas(currentItemState,'created');
}

function itemStateWasLabeled(currentItemState) {
  return _itemStateWas(currentItemState,'labeled');
}

function itemStateWasTransferable(currentItemState) {
  return _itemStateWas(currentItemState,'transferable');
}

function itemStateWasTransfered(currentItemState) {
  return _itemStateWas(currentItemState,'transfered');
}

function itemStateWasSold(currentItemState) {
  return _itemStateWas(currentItemState,'sold');
}

function _itemStateWas(currentItemState, checkItemState) {
  return _getItemStateIndex(currentItemState) >= _getItemStateIndex(checkItemState);
}

function _getItemStateIndex(itemState) {
  var itemStates = ['created', 'labeled', 'transferable', 'transfered', 'sold'];
  for (i = 0; i < itemStates.length; i++) {
    if (itemStates[i] == itemState) {
      return i;
    }
  }

  return -1;
}
