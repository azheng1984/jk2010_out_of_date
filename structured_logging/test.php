//    public function testInvaidName() {
//        try {
//            Logger::warn(['name' => '.']);
//            $this->fail();
//        } catch (LoggingException $e) {
//            $this->assertFalse(
//                file_exists(Config::getAppRootPath() . '/log/app.log')
//            );
//        } catch (Exception $e) {
//            $this->fail();
//        }
//        try {
//            Logger::warn(['name' => '.name']);
//            $this->fail();
//        } catch (LoggingException $e) {
//            $this->assertFalse(
//                file_exists(Config::getAppRootPath() . '/log/app.log')
//            );
//        } catch (Exception $e) {
//            $this->fail();
//        }
//        try {
//            Logger::warn(['name' => 'name.']);
//            $this->fail();
//        } catch (LoggingException $e) {
//            $this->assertFalse(
//                file_exists(Config::getAppRootPath() . '/log/app.log')
//            );
//        } catch (Exception $e) {
//            $this->fail();
//        }
//    }
//
//    /**
//     * @expectedException Hyperframework\Logging\LoggingException
//     */
//    public function testInvaidData() {
//        try {
//            Logger::warn(['data' => '.']);
//        } catch (LoggingException $e) {
//            $this->assertFalse(
//                file_exists(Config::getAppRootPath() . '/log/app.log')
//            );
//            throw $e;
//        }
//    }
//
//    /**
//     * @expectedException Hyperframework\Logging\LoggingException
//     */
//    public function testInvaidDataKey() {
//        try {
//            Logger::warn(['.' => 'value']);
//        } catch (LoggingException $e) {
//            $this->assertFalse(
//                file_exists(Config::getAppRootPath() . '/log/app.log')
//            );
//            throw $e;
//        }
//    }
//
//    /**
//     * @expectedException Hyperframework\Logging\LoggingException
//     */
//    public function testInvaidSecondLevelDataKey() {
//        try {
//            Logger::warn(['key' => ['.' => 'value']]);
//        } catch (LoggingException $e) {
//            $this->assertFalse(
//                file_exists(Config::getAppRootPath() . '/log/app.log')
//            );
//            throw $e;
//        }
//    }


