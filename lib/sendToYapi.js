const axios = require('axios')

module.exports = function (url, token, json) {
  return new Promise((resolve, reject) => {
    axios.post(url, {
      type: 'swagger',
      merge: 'merge',
      token: token,
      json: JSON.stringify(json)
    })
    .then(function (response) {
      resolve()
    })
    .catch(function (error) {
      reject()
    })
  })
}
