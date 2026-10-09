import Swal from 'sweetalert2'
import DOMPurify from 'dompurify'

// Shared boundary for every modal that can contain user-controlled names/errors.
const alerts = Swal.mixin({})
const policy = {ALLOWED_TAGS: ['b','strong','i','em','u','br','p','div','span','small','ul','ol','li','table','thead','tbody','tr','th','td','h3','h4','h5','h6','code','pre'], ALLOWED_ATTR: ['class','style','aria-hidden']}
const clean = value => typeof value === 'string' || value?.nodeType ? DOMPurify.sanitize(value, policy) : value
const cleanOptions = options => {
  const result = {...options}
  for (const key of ['html', 'title', 'footer', 'confirmButtonText', 'cancelButtonText', 'denyButtonText']) {
    if (Object.hasOwn(result, key)) result[key] = clean(result[key])
  }
  return result
}
alerts.fire = function (...arguments_) {
  if (arguments_[0] && typeof arguments_[0] === 'object') {
    return Swal.fire.call(this, cleanOptions(arguments_[0]))
  }
  arguments_[0] = clean(arguments_[0])
  return Swal.fire.apply(this, arguments_)
}
alerts.mixin = function (options) { return Swal.mixin.call(this, cleanOptions(options)) }
alerts.update = function (options) { return Swal.update.call(this, cleanOptions(options)) }
alerts.showValidationMessage = message => Swal.showValidationMessage(clean(message))
export default alerts
