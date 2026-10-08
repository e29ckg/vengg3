import {test} from 'node:test'
import assert from 'node:assert/strict'
import {JSDOM} from 'jsdom'
const dom = new JSDOM('<!doctype html><html><body></body></html>', {url: 'http://localhost/', pretendToBeVisual: true})
for (const key of ['window','document','navigator','HTMLElement','Element','Node','HTMLInputElement','HTMLButtonElement','HTMLVideoElement','HTMLAudioElement','DOMParser','MutationObserver','Event','CustomEvent','getComputedStyle']) {
  Object.defineProperty(globalThis,key,{value: key === 'window' ? dom.window : dom.window[key],configurable:true})
}
dom.window.matchMedia = () => ({matches:false,addEventListener(){},removeEventListener(){}})
dom.window.scrollTo = () => {}
const alerts = (await import('../src/services/alerts.js')).default

test('stored HTML cannot inject scripts, images, or event handlers into a modal', async () => {
  const promise = alerts.fire({title:'<img src=x onerror="window.pwned=1">name',html:'<b>safe</b><script>window.pwned=1</script><svg onload="window.pwned=1"></svg><img src=x onerror="window.pwned=1">'})
  assert.equal(document.querySelector('#swal2-html-container b').textContent,'safe')
  assert.equal(document.querySelector('#swal2-html-container').querySelector('script,img,svg,[onerror],[onload]'),null)
  assert.equal(document.querySelector('#swal2-title img'),null)
  assert.equal(dom.window.pwned,undefined)
  alerts.close(); await promise
})
test('mixin defaults and validation messages are also sanitized', async () => {
  const toast = alerts.mixin({toast:true,position:'top-end',title:'<img src=x onerror="window.pwned=1">safe'})
  const promise = toast.fire({showConfirmButton:false})
  assert.equal(document.querySelector('#swal2-title img'),null)
  assert.ok(document.querySelector('.swal2-toast'))
  alerts.showValidationMessage('<img src=x onerror="window.pwned=1"><b>retry</b>')
  assert.equal(document.querySelector('#swal2-validation-message img'),null)
  assert.equal(document.querySelector('#swal2-validation-message b').textContent,'retry')
  alerts.close(); await promise
})
