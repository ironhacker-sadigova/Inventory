Vagrant.configure("2") do |config|

    config.vm.box = "precise32"
    config.vm.box_url = "http://files.vagrantup.com/precise32.box"

    if ARGV[1] == 'tests'
        ARGV.delete_at(1)
        tests = true
    else
        tests = false
    end

    if tests == false
        config.vm.network :private_network, ip: "192.168.56.101"
        config.vm.network :forwarded_port, guest: 80, host: 8000
        config.ssh.forward_agent = true

        config.vm.provider "virtualbox" do |v|
            v.customize ["modifyvm", :id, "--natdnshostresolver1", "on"]
            v.customize ["modifyvm", :id, "--memory", 1024]
            v.customize ["modifyvm", :id, "--name", "inventory"]
        end

        config.vm.provision :shell, :path => "data/vagrant/bootstrap.sh"
    end

    if tests == true
        config.vm.provider "virtualbox" do |v|
            v.customize ["modifyvm", :id, "--memory", 1024]
        end

        config.vm.provision :shell, :path => "data/vagrant/bootstrap-tests.sh"
    end

end
