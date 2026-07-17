/** 
  *   @desc Base JavaScript object properties
  *   @package KCFinder
  *   @version 3.12
  *   @license http://opensource.org/licenses/GPL-3.0 GPLv3
  *   @license http://opensource.org/licenses/LGPL-3.0 LGPLv3
  */

var _ = {
    opener: {},
    support: {},
    files: [],
    clipboard: [],
    labels: [],
    shows: [],
    orders: [],
    cms: "",
    selector: {
        enabled: false,
        multiple: false,
        targetOrigin: null,
        error: null
    },
    search: {
        enabled: false,
        minChars: 2,
        maxResults: 100,
        debounceMs: 350
    },
    scrollbarWidth: 20
};
