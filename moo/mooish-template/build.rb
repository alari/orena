#!/usr/bin/env ruby

# USAGE:
# 
# Full:
# ./build.rb
# 
# Fx.Tween, DomReady including all dependencies:
# ./build.rb Fx.Tween DomReady

# todo looks like it builds an empty file if nothing from brawndo in list

require 'rubygems'
require 'json'
require 'yaml'
require 'trollop'

module MooTools
  class Build
    
    attr_reader   :included
    attr_accessor :build_path
    attr_accessor :dependency_paths
    attr_accessor :data
    
    def initialize(opts={})
      @path             = opts[:path] || File.dirname(__FILE__)
      @build_path       = opts[:build_path] || @path + '/mootools.js'
      @dependency_paths = opts[:dependency_paths] || [@path + '/Source']
      @dependency_file  = opts[:dependency_file] || 'scripts.json'
      
      @scripts  = []
      @included = []
      @data     = {}
      
      @dependency_paths.each do |dependency_path|
        json = JSON.load(File.read( dependency_path[:path] + "/#{@dependency_file}" ))
        json.each_pair do |folder, group|
          group.each_pair do |script, properties|
            @data[script] = {:folder => "#{dependency_path[:path]}/#{folder}", :deps => properties["deps"]}
          end
        end
      end
    end
    
    def full_build
      @data.each_key { |name| load_script name }
      @string
    end
    
    def load_script(name)
      return if @included.index(name) || name == 'None';
      unless @data.key? name
        puts "Script '#{name}' not found!"
        throw :script_not_found
      end
      @included.push name
      @data[name][:deps].each { |dep| load_script dep }
      @string ||= ""
      @string << File.read("#{@data[name][:folder]}/#{name}.js") << "\n"
    end
    
    def build      
      @string ||= full_build      
      @string.sub!('%build%', build_number)
      @string
    end
    alias :to_s :build
    
    def build_number
      ref =  File.read(File.dirname(__FILE__) + '/.git/HEAD').chomp.match(/ref: (.*)/)[1]
      return File.read(File.dirname(__FILE__) + "/.git/#{ref}").chomp
    end
    
    def save(filename)
      File.open(filename, 'w') { |fh| fh.write to_s }
    end
    
    def save!
      save build_path
    end
    
    def self.build!(definition, builder = MooTools::Build.new)
      catch :script_not_found do
        if definition && definition[:modules] && definition[:modules].length > 0
          definition[:modules].each { |script| builder.load_script(script) }
        else
          mootools.full_build
        end
      end
      
      puts "MooTools Built '#{builder.build_path}'"
      print "\tIncluded Scripts:","\n    "
      puts builder.included.join(" ")
      builder.save!      
    end
    
  end
end

if __FILE__ == $0
  
  opts = Trollop::options do
    opt :build_config,
      "Build Configuration file (YAML file where all the dependency paths live",
      :type => :string,
      :default => File.join(File.dirname(__FILE__), "build.yml")
    opt :definitions,
      "Projects definition file (YAML file defining all the projects you work on", 
      :type => :string, 
      :default => File.join(File.dirname(__FILE__), "projects.yml")
    opt :project,
      "Project to build",
      :type => :string
    opt :override_list,
      "Override projects.yml and project selection and just build a file with a list of classes",
      :type => :string,
      :short => 'l'
    opt :output,
      "Output file location. This overrides any project.yml definition and defaults to brawndo.js if you provide an override list",
      :type => :string,
      :default => 'brawndo.js'
  end
  
  Trollop::die "could not find build configuration file at: #{opts[:build_config]}" unless File.exists?(opts[:build_config])
  conf = YAML.load_file(opts[:build_config])
  dependency_paths = conf[:dependency_paths]

  Trollop::die "you need to either specify a project a specify overrides." unless opts[:project] || opts[:override_list]
  if opts[:project].nil? && opts[:override_list]
    build_path = opts[:output]
    definition = { :modules => opts[:override_list].split, :build_path => build_path }
  else
    Trollop::die "could not find projects definition file at: #{opts[:definitions]}" unless File.exists?(opts[:definitions])
    definitions = YAML.load_file(opts[:definitions])
    definition = definitions[opts[:project].intern]
    build_path = definition[:build_path]
  end
  
  builder = MooTools::Build.new(
    :dependency_paths => dependency_paths, 
    :build_path => build_path
  )

  MooTools::Build.build! definition, builder
end
