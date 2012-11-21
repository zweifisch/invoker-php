should = chai.should()
mocha.setup 'bdd'

{batch,invoke} = invoker
{User,Path} = invoker.classes

describe 'User',->

	describe 'save',->
		it 'should add a user', (done)->
			batch (onBatchDone)->
				User.listUsers() (users)->
					users.length.should.equal 0
				user = new User 'foo'
				user.save() (result)->
					result.should.equal true
				User.listUsers() (users)->
					users.length.should.equal 1
				onBatchDone done

describe 'Path',->

	describe 'access controll',->
		it 'should not allow access to certain methods', (done)->
			should.not.exist Path.scanDir

			invoke ['Path','scanDir'], (result,code)->
				code.should.equal 404
				done()

