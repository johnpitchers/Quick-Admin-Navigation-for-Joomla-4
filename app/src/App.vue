<template>
  <div id="qan-app">
    <div id="quickadminnav-modal" v-if="state.showModal" :class="{'loading': state.loading}">
      <div class="qan-m-inner">
        <div class="qan-m-inner-container">
          <div class="qan-m-header p-3">
            <div class="input-group">
              <span class="input-group-text" id="basic-addon1"><span class="icon-search"></span></span>
              <input v-model="state.searchText" @keydown="doKeyEventsDown($event)" @keyup="doKeyEventsUp($event)" type="text" class="form-control" ref="quicksearchinputbox" placeholder="Quick search..." aria-label="Quick search...">
            </div>
          </div>
          <div class="qan-m-body">
            <ul v-if="state.results.length" class="list-group">
              <li v-for="(result,key) in state.results" @click="handleClick($event, key)" :key="key" class="px-3" :class="{'selected':key === state.selected.key}" :ref="(el)=>{key===state.selected.key?'selected':''}">
                <i v-if=" result.item.icon && result.item.icon.length
              " :class="'me-1 icon-fw icon-'+result.item.icon"></i>
                <span v-html="result.item.display"></span>
              </li>
            </ul>
            <p v-else class="mb-2 px-3">Nothing to see here</p>
          </div>
        </div>
        <div v-if="state.loading" class="qan-spinner spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>

import Fuse from "fuse.js";
import {
  reactive,
  ref,
  onMounted,
  nextTick
} from 'vue';

export default {
  name: 'App',
  setup() {
    const quicksearchinputbox = ref(null);
    const selected = ref(null);
    const state = reactive({
      items: [],
      showModal: false,
      timer: Date.now(),
      hayStack: window.quickadminnavItems,
      results: {},
      searchText: '',
      loading: false,
      selected: {
        key: 0,
        link: ''
      },
      fuseOpts: {
        // isCaseSensitive: false,
        // includeScore: false,
        shouldSort: false,
        //includeMatches: true,
        // findAllMatches: false,
        minMatchCharLength: 2,
        location: 0,
        threshold: 0.3,
        // distance: 100,
        // useExtendedSearch: false,
        ignoreLocation: true,
        // ignoreFieldNorm: false,
        // fieldNormWeight: 1,
        keys: [
          "searchKey"
        ]
      }
    });

    onMounted(() => {
      state.items = window.quickadminnavItems;
      addKeyboardEvents();
    })

    function addKeyboardEvents() {
      document.addEventListener('keydown', (e) => {
        if (e.key === "Escape" && state.showModal) {
          setOrToggleShow(false);
        }
        if (e.key === "Shift" && !state.showModal) {
          let interval = Date.now() - state.timer;
          state.timer = Date.now();
          if (interval < 300) {
            setOrToggleShow(true);
          }
        }
      });
    }

    function setOrToggleShow(toggleState = null) {
      if (!toggleState) {
        state.showModal = !state.showModal;
      } else {
        state.showModal = toggleState;
      }
      if (state.showModal) {
        nextTick(() => {
          quicksearchinputbox.value.focus();
          addMouseEvents();
        });
      }
    }

    function addMouseEvents() {

      //Clicking the modal background will close it. Clicking within it will not close it.
      let target = "quickadminnav-modal";
      document.getElementById(target).addEventListener('mouseup', (e) => {
        if (e.target.id === target) {
          setOrToggleShow(false);
        }
      })
    }

    function doKeyEventsDown(e) {
      if (e.key === 'Enter') {
        goToSelected();
      }
      if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
        e.preventDefault();
        changeSelected(e.key === 'ArrowDown' ? 1 : -1);
      }
    }

    function doKeyEventsUp() {
      doSearch();
    }

    function handleClick($event, key) {
      $event.preventDefault();
      state.selected = {
        key: key,
        link: state.results[key].item.link
      };
      quicksearchinputbox.value.focus();
      goToSelected();
    }

    function goToSelected() {
      if (state.selected.link.startsWith('https')) {
        state.showModal = false;
        window.open(state.selected.link, '_blank');
      } else {
        state.loading = true;
        window.location.href = state.selected.link;
      }
    }

    function doSearch(text = '') {
      let fuse = new Fuse(state.hayStack, state.fuseOpts);
      let searchTerm = text !== '' ? text : state.searchText;
      state.fuseOpts.shouldSort = true;
      if (state.searchText.length < 2) {
        searchTerm = "~^all^~";
        state.fuseOpts.shouldSort = false;
      }
      state.results = fuse.search(searchTerm);
      changeSelected();
    }

    function changeSelected(dir = 0) {
      if (!state.results.length) {
        state.selected = {
          key: 0,
          link: ''
        };
        return;
      }
      state.selected.key += dir;
      if (state.selected.key < 0) state.selected.key = state.results.length - 1;
      if (state.selected.key > state.results.length - 1) state.selected.key = 0;
      state.selected.link = state.results[state.selected.key].item.link;
      nextTick(() => {
        // The below line should use a ref but after 4 hours I gave up trying to get Vue3 to recognise a dynamic ref.
        // this.$refs works fine in Vue2!
        document.querySelector('li.selected').scrollIntoViewIfNeeded();
      });
    }

    return {state, quicksearchinputbox, selected, doKeyEventsDown, doKeyEventsUp, handleClick};
  }
}
</script>

<style lang="scss">
#quickadminnav-modal {
  position: fixed;
  top: 0;
  left: 0;
  bottom: 0;
  right: 0;
  background: rgba(0, 0, 0, 0.8);
  color: #fff;
  z-index: 1100;
  display: flex;
  justify-content: center;
  align-items: center;

  .qan-m-inner {
    margin: 20px;
    width: 410px;
    max-width: 100%;
    background: #fff;
    color: #333;
    position: relative;
    border-radius: 7px;
    overflow: hidden;
  }

  .qan-m-header {
    background: #f0f4fb;
    border-bottom: 1px solid #e0e0e0;
    box-shadow: 0 3px 5px rgb(0 0 0 / 8%);
    position: relative;
  }

  .qan-m-body {
    height: 350px;
    max-height: 65vh;
    overflow: hidden;
    overflow-y: auto;
    font-size: 0.9rem;

    &::-webkit-scrollbar-track {
      background-color: transparent;

    }

    &::-webkit-scrollbar {
      width: 10px;
      background-color: transparent;
    }

    &::-webkit-scrollbar-thumb {
      border-radius: 8px;
      background-color: #ddd;
      border: 3px solid #fff;
    }
  }

  .list-group {
    li {
      cursor: pointer;
      white-space: nowrap;
      padding: 5px 0 4px;
      border-bottom: 1px dashed #ddd;

      &.selected {
        background: #3d618f;
        color: #fff;
        //margin: -1px 0;
      }
    }
  }

  &.loading {
    .qan-m-inner {
      opacity: 0.95;
    }

    .qan-m-inner-container {
      opacity: 0.6;
    }
  }

  .qan-spinner {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 40px;
    height: 40px;
    border: 7px solid #33446622;
    border-radius: 40px;
    border-bottom-color: #346;
    animation: rotation 1s linear infinite;
  }
}

@keyframes rotation {
  from {
    transform: translate(-50%, -50%) rotate(0deg);
  }
  to {
    transform: translate(-50%, -50%) rotate(359deg);
  }
}

</style>
