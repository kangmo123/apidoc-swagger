const axios = require('axios')
const json = require('./output/swagger.json')

axios.post('http://impai.oa.com/api/open/import_data', {
  type: 'swagger',
  merge: 'merge',
  token: '76c5dad51ea5fb948ed5613d3ac1546174ea4d2b97382bd69f5d9dd417432038',
  json: JSON.stringify(json)
})
.then(function (response) {
  console.log(response)
})
.catch(function (error) {
  console.log(error)
})
